# Stripe Connect — Platform Fee on Every Tenant Sale

We charge our tenants two ways:

1. A **subscription fee** for the SaaS — see [`BILLING_THE_TENANTS.md`](./BILLING_THE_TENANTS.md).
2. A **platform fee** taken automatically out of every sale a tenant
   makes through Stripe — this doc.

The platform fee is implemented with [Stripe Connect](https://stripe.com/connect).
We are the **platform**; tenants are **connected accounts**. When a
tenant's customer pays an invoice, the charge is split at Stripe: most of
it lands in the tenant's Stripe balance, our `application_fee_amount`
lands in ours. No human in the middle, no monthly reconciliation.

---

## 1. Connect account flavour

Pick **Standard** for v1.

| Account type | Who owns dashboard | Compliance burden | UX | When to use |
| ------------ | ------------------ | ----------------- | --- | ----------- |
| **Standard** | Connected account (tenant) | Stripe handles KYC, 1099-K, dispute mediation directly with tenant | Tenant uses Stripe dashboard for refunds, payouts, taxes | **Default for v1** — least liability for us |
| Express | Tenant via Stripe-hosted UI | Stripe handles compliance, we own onboarding UX | Tenant sees a slim "express dashboard" | When we want our own onboarding flow |
| Custom | Us (platform) | We handle everything, including KYC, payouts, disputes | Bespoke | Only when we want to be the merchant of record — rejected |

**Decision (AD-011 in `ARCHITECTURE.md`).** Standard. Tenants link their
own Stripe account; we never see card numbers, we never own funds beyond
the moment they pass through (with `transfer_data`) or get charged
directly.

If a future tenant insists on us being merchant of record, we can offer
Custom on the `scale` plan with a per-quote setup.

---

## 2. Onboarding

### 2.1 OAuth (Standard accounts)

The first time a tenant opens
`/admin/settings/payment-providers/stripe-connect`:

```php
// app/Http/Controllers/Central/StripeConnectController.php
public function redirect(Request $request)
{
    $state = Crypt::encrypt([
        'tenant_id' => tenant()->id,
        'csrf'      => Str::random(32),
        'ttl'       => now()->addMinutes(10)->timestamp,
    ]);

    return redirect()->away(
        'https://connect.stripe.com/oauth/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => config('services.stripe.connect_client_id'),
            'scope'         => 'read_write',
            'state'         => $state,
            'redirect_uri'  => route('central.stripe.callback'),
            'stripe_user[email]' => auth()->user()->email,
            'stripe_user[business_type]' => 'company',
        ])
    );
}

public function callback(Request $request, StripeClient $stripe)
{
    $state = Crypt::decrypt($request->query('state'));
    abort_if($state['ttl'] < now()->timestamp, 410);
    $tenant = Tenant::findOrFail($state['tenant_id']);

    $resp = $stripe->oauth->token([
        'grant_type' => 'authorization_code',
        'code'       => $request->query('code'),
    ]);

    tenancy()->run($tenant, function () use ($resp) {
        setting([
            'stripe_connect.account_id'  => $resp->stripe_user_id,
            'stripe_connect.publishable' => $resp->stripe_publishable_key,
            'stripe_connect.connected_at'=> now()->toIso8601String(),
        ])->save();
    });

    return redirect()->to('https://' . $tenant->primaryDomain() . '/admin/settings/payment-providers/stripe-connect?connected=1');
}
```

The `state` parameter carries tenant identity through Stripe's redirect
in encrypted form so the callback can re-bootstrap the right tenant
context (the callback hits the central host).

### 2.2 Account capabilities

After OAuth completes, we call:

```php
$stripe->accounts->update($accountId, [
    'capabilities' => [
        'card_payments' => ['requested' => true],
        'transfers'     => ['requested' => true],
    ],
]);
```

A tenant whose account is missing required info (`charges_enabled = false`
or `payouts_enabled = false`) sees a banner in their admin with a deep
link back to Stripe to finish onboarding. We poll `account.updated`
webhooks to know when they're cleared.

---

## 3. Taking the fee — destination charges

For each invoice the tenant's customer pays:

```php
$intent = $stripe->paymentIntents->create([
    'amount'   => $invoice->total_cents,
    'currency' => $invoice->currency,
    'customer' => $stripeCustomerId,
    'description' => "Invoice #{$invoice->number}",
    'transfer_data' => [
        'destination' => setting('stripe_connect.account_id'),
    ],
    'on_behalf_of' => setting('stripe_connect.account_id'),
    'application_fee_amount' => $this->platformFeeFor($invoice),
    'metadata' => [
        'tenant_id'  => tenant()->id,
        'invoice_id' => $invoice->id,
    ],
], [
    // No stripe_account header — this is a direct charge on the
    // platform, with transfer_data shipping the rest to the tenant.
]);
```

What this means at Stripe:

- The charge is created on **our** Stripe account.
- The funds (minus `application_fee_amount` and Stripe's own processing
  fee) are transferred to the tenant's connected account immediately.
- The platform fee lands in our balance.
- The customer's statement descriptor shows the tenant's business
  (because of `on_behalf_of`).
- Stripe's processing fees (the 1.4% + €0.25 type cost) are deducted
  from the tenant's transfer by default. The tenant absorbs Stripe's
  fee; we absorb only what we explicitly charge as `application_fee_amount`.

### 3.1 Fee calculation

```php
public function platformFeeFor(Invoice $invoice): int
{
    $plan = tenant()->plan;     // App\CentralPlan instance
    $bps  = $plan->platform_fee_bps;        // e.g. 200 = 2.00%
    $flat = $plan->platform_fee_flat_cents; // e.g. 10  = €0.10

    return (int) round($invoice->total_cents * $bps / 10_000) + $flat;
}
```

Configured on the `central_plans` table:

| Plan | `platform_fee_bps` | `platform_fee_flat_cents` | Effective on a €100 sale |
| ---- | ------------------ | ------------------------- | ------------------------ |
| Starter | 200 (2.00%) | 10 (€0.10) | €2.10 to us |
| Pro     | 100 (1.00%) | 0          | €1.00 to us |
| Scale   | 50 (0.50%)  | 0          | €0.50 to us |

The fee is mandatory and not configurable by the tenant; it is a
SaaS-level setting. Disclose in T&Cs and on the pricing page.

### 3.2 Currency handling

`transfer_data.destination` ships in the same currency as the charge.
Stripe handles cross-currency conversion to the tenant's payout
currency at their published rate (a separate fee tenants absorb). The
`application_fee_amount` is also in the charge currency; it converts to
our payout currency on settlement at Stripe's rate.

We do **not** try to outsmart this. If a tenant complains about the
conversion rate, point them at Stripe's docs.

---

## 4. Webhooks

Two webhook endpoints:

### 4.1 Platform webhook — `POST central.paymenter.io/webhook/stripe/platform`

Listens for events about the **platform account**:

| Event | Action |
| ----- | ------ |
| `account.updated` | Refresh capabilities, update tenant banner state |
| `account.application.deauthorized` | Tenant disconnected us. Clear settings, suspend new charges on that gateway, notify tenant admin. |
| `application_fee.refunded` | A refund clawed back part of our fee. Update central revenue. |
| `payout.failed` (on platform) | Operator alert (our funds didn't arrive). |
| `charge.refunded` (where we are platform) | Adjust local Order/Invoice status. |
| `charge.dispute.created` | Alert operator; let tenant know per Stripe's notifications. |

Signature verified with our **platform** webhook secret.

### 4.2 Connected-account webhook — same endpoint, different secret

We can also listen for events on connected accounts. For Standard accounts
the tenant can set up their own webhooks for richer integrations. We
only need the platform-level events above.

If we want per-tenant `customer.subscription.*` events to drive
Paymenter's renewal logic, we configure a separate platform-level
webhook with `Stripe-Account` headers and a single endpoint. Code:

```php
public function handle(Request $request, StripeClient $stripe)
{
    $event = $stripe->webhooks->constructEvent(
        $request->getContent(),
        $request->header('Stripe-Signature'),
        config('services.stripe.webhook_secret'),
    );

    $stripeAccount = $event->account ?? null;  // null = platform event

    if ($stripeAccount) {
        $tenant = Tenant::whereJsonContains('data->stripe_connect_account', $stripeAccount)->first();
        abort_unless($tenant, 404);
        tenancy()->initialize($tenant);
    }

    dispatch(new HandleStripeEventJob($event->id));
    return response('', 200);
}
```

The job is serialised with the tenant id and bootstraps tenancy again on
the worker.

---

## 5. Refunds

When a tenant refunds in their Stripe dashboard (or via Paymenter's
"refund" button calling `refunds.create`):

```php
$stripe->refunds->create([
    'payment_intent' => $invoice->stripe_intent_id,
    'amount'         => $amount,
    'refund_application_fee' => true,   // claw back our cut proportionally
    'reverse_transfer'       => true,   // pull funds back from connected account
]);
```

`refund_application_fee = true` is the default per our SaaS T&Cs: full
refund → full fee refund; partial refund → proportional fee refund.

For abuse cases (chargebacks, frauds) we can take the fee anyway by
setting `refund_application_fee = false`; reserved for operator override.

---

## 6. Disputes

For Standard accounts:

- Cardholder disputes go to the **connected account** (tenant's Stripe).
- The disputed amount is debited from the tenant's balance immediately.
- If the dispute is lost, the tenant absorbs the chargeback fee.
- The platform fee on the disputed charge is **not** automatically
  refunded — operator decides per dispute (typically yes; record in
  central audit).

We surface dispute notifications to the tenant via the
`charge.dispute.created` webhook and email; we do not litigate on their
behalf.

---

## 7. Reporting & payouts

- **Tenant** sees their own Stripe dashboard. They get the actual
  ledger.
- **Operator** sees an aggregated view in the central panel: MRR (from
  subscriptions), transaction volume per tenant, fees collected,
  refund-adjusted net fees, top tenants by volume, anomaly alerts.
- Source of truth: pull the platform's `BalanceTransaction` list from
  Stripe daily, store in a `stripe_platform_ledger` table. Reconcile
  monthly.

```php
Schema::create('stripe_platform_ledger', function (Blueprint $t) {
    $t->id();
    $t->string('stripe_id')->unique();         // bt_...
    $t->uuid('tenant_id')->nullable()->index();
    $t->string('type');                         // charge|refund|application_fee|application_fee_refund|payout|...
    $t->bigInteger('amount_cents');             // signed
    $t->string('currency', 3);
    $t->jsonb('source');                        // raw event reference
    $t->timestamp('available_at');
    $t->timestamps();
});
```

---

## 8. Deauthorisation

If a tenant clicks "Disconnect" in their Stripe dashboard, we get the
`account.application.deauthorized` webhook:

- Clear `setting('stripe_connect.*')` for that tenant.
- Mark the Stripe gateway disabled on the tenant.
- Email the tenant admin + central operator.
- New checkouts on that tenant fall back to whatever other gateway is
  enabled, or display "Payments currently unavailable".
- Existing subscriptions / orders are **not** automatically cancelled
  — they fail at the next renewal attempt and Paymenter's existing
  overdue flow handles the rest.

Reconnecting later re-runs the OAuth flow; we treat the new connection
as the same gateway and resume.

---

## 9. Edge cases

| Case | Behaviour |
| ---- | --------- |
| Tenant in a country Stripe Connect does not support | Show an error at onboarding; offer the legacy non-Connect Stripe gateway (no platform fee — operator should restrict by plan if they want). |
| Tenant charging in a currency our platform account does not support | Stripe will reject; we log and tell the tenant to enable that currency in their connected account. |
| Tenant clones a charge and bypasses our intent (theoretical) | Cannot, because they don't have our publishable key for the platform side; their secret key cannot create platform-fee charges except through our intent. |
| Tenant tries to set `application_fee_amount = 0` directly | The PaymentIntent is created by **our** code on the platform; tenant code never touches it. |
| We need to refund a fee we mistakenly took | `application_fee.refund` API call. Operator-only action; logged. |

---

## 10. Compliance

- Privacy policy: discloses that we are the Stripe platform and receive
  charge metadata (amount, currency, timestamps, customer email) for
  every transaction.
- T&Cs: discloses the platform fee schedule per plan.
- Connect agreement: tenants must accept Stripe's Connect terms during
  OAuth (Stripe handles this in their flow).
- 1099-K: Stripe issues to tenants directly for Standard accounts.
- DPA: we extend our Data Processing Agreement to include Stripe as a
  subprocessor.

---

## 11. Migration from existing Paymenter Stripe gateway

Some tenants will have been using Paymenter's plain Stripe gateway
before we turned on Connect. Migration steps:

1. On rollout, mark the legacy `Stripe` gateway as `deprecated` in the
   catalogue (still works for existing setups, hidden for new ones).
2. Show a banner in tenant admin: "Switch to Stripe Connect → benefits".
3. Tenant clicks → OAuth → settings switch to `stripe_connect.*`.
4. Old `stripe_secret_key` setting is cleared (not migrated; the
   connected account is a different relationship).
5. After 90 days, hard-disable the legacy gateway and require Connect.

---

## 12. Test plan

Non-negotiable before shipping Connect:

- OAuth round-trip in Stripe test mode.
- Charge creation with destination + application fee; verify Stripe
  dashboard shows the split.
- Refund with `refund_application_fee=true`; verify both balances move.
- Dispute simulation via Stripe's test cards (`4000000000000259`);
  verify webhook fires and tenant is notified.
- Disconnect from tenant's test dashboard; verify settings cleared and
  banner appears.
- Currency cross-conversion sanity (charge in EUR, payout in USD).
- Reconciliation job pulls a day of `BalanceTransaction` and matches
  ledger to events.
