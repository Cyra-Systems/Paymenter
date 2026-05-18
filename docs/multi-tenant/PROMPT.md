# Master Implementation Prompt

A copy-paste prompt to hand to Claude (or another capable LLM in a coding
agent harness) to drive the multi-tenant conversion. Pair it with a Claude
Code session in this repo so the model can read `CLAUDE.md` and the docs.

---

## How to use

1. Start a Claude Code session in this repo.
2. Paste the prompt below into the first message.
3. Watch for the agent to read the docs and propose a plan for the
   current phase.
4. Approve or correct the plan before letting it write code.

You can run the prompt **once per phase**. Each phase is independently
shippable. Do not skip phases.

---

## The prompt

```text
You are working in a Paymenter fork being converted into a multi-tenant
SaaS ("Paymenter-as-a-Service").

Authoritative docs, in this repo, that you MUST read in this order before
proposing or writing anything:

  1. CLAUDE.md (repo root)
  2. docs/multi-tenant/README.md
  3. docs/multi-tenant/ARCHITECTURE.md
  4. docs/multi-tenant/IMPLEMENTATION_PLAN.md
  5. docs/multi-tenant/TENANT_ISOLATION.md
  6. docs/multi-tenant/PROVISIONING.md
  7. docs/multi-tenant/DOMAIN_ROUTING.md
  8. docs/multi-tenant/EXTENSIONS.md
  9. docs/multi-tenant/THEMES.md
 10. docs/multi-tenant/STRIPE_CONNECT.md
 11. docs/multi-tenant/BILLING_THE_TENANTS.md
 12. docs/multi-tenant/MIGRATION_GUIDE.md

Then read these to ground yourself in the codebase you'll be modifying:

  - composer.json
  - bootstrap/app.php
  - app/Providers/AppServiceProvider.php
  - app/Providers/Filament/AdminPanelProvider.php
  - app/Providers/SettingsProvider.php
  - app/Models/Model.php
  - app/Models/User.php
  - config/database.php
  - config/auth.php
  - routes/web.php
  - database/migrations/2024_02_15_122225_create_settings_table.php
  - one or two example Filament resources under app/Admin/Resources/

## Your task

Execute exactly ONE phase from docs/multi-tenant/IMPLEMENTATION_PLAN.md.
Default to the lowest-numbered phase that is not yet complete. The user
may override with a specific phase number; obey them.

For the phase you are executing:

1. State which phase you are working on and quote its "Done when"
   checklist.
2. Identify each file you will create or modify, with one-line
   justifications.
3. STOP and ask the human to approve the file plan before writing code.
4. Once approved, implement, running:
     - php artisan test
     - ./vendor/bin/pint
     - ./vendor/bin/phpstan analyse
   after each meaningful change.
5. Add a feature test that exercises the new behaviour for at least two
   seeded tenants (or, for central-only changes, for at least the
   central app plus one tenant).
6. Update the relevant doc(s) under docs/multi-tenant/ to match what
   you actually built. If you deviated from the doc, update the doc;
   docs are source of truth.
7. Commit with a message of the form
   "phase {n}: {short description}"
   on the branch claude/multi-tenant-phase-{n}.

## Hard rules

- Use stancl/tenancy v4 in single-database mode. Do not invent a
  parallel tenancy mechanism. Do not switch to database-per-tenant.
- Tenant identification is by domain, never by URL path.
- Database is Postgres 16+ with Row-Level Security. Two roles:
  paymenter_app (NOBYPASSRLS) for tenant traffic, paymenter_admin
  (BYPASSRLS) for central code only. Two Laravel connections: pg
  (default) and pg_admin.
- Every tenant-scoped table has a tenant_id uuid column, a FORCE ROW
  LEVEL SECURITY policy, and a DEFAULT of
  current_setting('app.tenant_id', true)::uuid. Use the TenantScoped
  migration trait. Do not bypass RLS in application code.
- Stripe Connect (Standard accounts) is the only sanctioned payment
  path for tenant→customer charges. application_fee_amount is mandatory
  and configured per central_plans row. The legacy plain Stripe gateway
  is deprecated.
- Extensions are operator-curated. Every extension ships an
  extension.json manifest declaring capabilities. Undeclared
  capabilities are denied at runtime. HTML/CSS/Markdown output from
  extensions goes through hardened sanitisers; CSP is emitted per
  response.
- Themes are curated by default. BYO themes are gated to Pro+ plans
  and run through a sandboxed Blade compiler (no @php, no {!! !!},
  file allow-list, CSS sanitiser, JS allow-list with SRI). Never
  weaken the sandbox.
- Stay rebase-compatible with upstream paymenter/paymenter: prefer
  additive providers/middleware/config over rewrites; if a core file
  must change, the diff must be minimal and noted in
  ARCHITECTURE.md or MIGRATION_GUIDE.md.
- Never commit secrets, .env files, OAuth keys, Stripe keys, or
  per-tenant config.
- Never weaken auth, encryption, signed URLs, RLS policies, or
  sanitisers to get a phase to pass.
- Do not run destructive operations (DELETE FROM tenants, rm -rf
  storage/app/tenant*, php artisan migrate:fresh on prod-like state,
  Stripe production charges) without asking the human first.

## Style

- Match the Paymenter codebase style. Run pint after each edit.
- Prefer the smallest change that satisfies the phase's "Done when".
- No commentary in code beyond what was already there. No emojis.
- When in doubt, ask the human a single clarifying question rather than
  guessing.

## Out of scope (do not start unprompted)

- Cross-tenant SSO.
- Tenant-uploaded PHP extensions.
- Multi-region tenant placement.
- Read replicas.
- The actual SaaS marketing site / pricing page UX.

## First message

Reply with:

  1. Which phase you are about to work on and why.
  2. The "Done when" checklist quoted verbatim.
  3. The file plan (created/modified) with one-line justifications.
  4. A single explicit "Should I proceed?" line.

Wait for approval.
```

---

## Variants

### Short version — for an ad-hoc question

When you don't need a full phase, just paste:

```text
Read CLAUDE.md and docs/multi-tenant/{topic}.md, then answer the
following with concrete file:line references from this repo:

  {your question}
```

### Phase-specific variant

Replace the "Your task" block in the main prompt with:

```text
Execute Phase {n} from docs/multi-tenant/IMPLEMENTATION_PLAN.md. Do not
do other phases.
```

### Skill-invocation variant (Claude Code)

If you're using the Claude Code skill at
`.claude/skills/multi-tenant-paymenter/SKILL.md`, you can simply say:

```text
Use the multi-tenant-paymenter skill to {action}.
```

The skill bootstraps Claude with the same reading list and rules; the
prompt above is the standalone equivalent for environments without
skills.

---

## Anti-patterns the prompt is built to prevent

- "Just add a `tenant_id` column" — the prompt forbids it; the docs
  explain why (`ARCHITECTURE.md` AD-001, AD-002).
- "Let me write a new tenancy package" — forbidden; `stancl/tenancy`
  is mandated.
- "I'll just sprinkle tenant logic in controllers" — the bootstrappers
  in `TENANT_ISOLATION.md` are the contract; controllers stay clean.
- "I'll quickly do Phases 1–5 in one PR" — forbidden; one phase per PR.

If the agent starts producing one of these, stop it and refer it back to
the relevant doc.
