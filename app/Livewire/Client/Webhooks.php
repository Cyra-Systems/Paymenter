<?php

namespace App\Livewire\Client;

use App\Jobs\WebhookDispatchJob;
use App\Livewire\Component;
use App\Models\Webhook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

class Webhooks extends Component
{
    public string $url = '';

    public array $selectedEvents = [];

    #[Locked]
    public ?string $newSecret = null;

    public const AVAILABLE_EVENTS = [
        'invoice.paid'   => 'Invoice Paid',
        'service.active' => 'Service Activated',
        'webhook.test'   => 'Webhook Test (ping)',
    ];

    public function rules(): array
    {
        return [
            'url'            => 'required|url|max:500',
            'selectedEvents' => 'required|array|min:1',
            'selectedEvents.*' => 'string|in:' . implode(',', array_keys(self::AVAILABLE_EVENTS)),
        ];
    }

    public function create(): void
    {
        $this->validate();

        $secret = Str::random(40);

        Webhook::create([
            'user_id' => Auth::id(),
            'url'     => $this->url,
            'secret'  => $secret,
            'events'  => $this->selectedEvents,
            'enabled' => true,
        ]);

        $this->newSecret = $secret;
        $this->reset('url', 'selectedEvents');
        $this->notify(__('Webhook created. Copy your secret now — it will not be shown again.'));
    }

    public function toggle(int $id): void
    {
        $webhook = Webhook::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$webhook) {
            $this->notify(__('Webhook not found.'), 'error');

            return;
        }

        $webhook->enabled = !$webhook->enabled;
        $webhook->save();
        $this->notify($webhook->enabled ? __('Webhook enabled.') : __('Webhook disabled.'));
    }

    public function delete(int $id): void
    {
        $webhook = Webhook::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$webhook) {
            $this->notify(__('Webhook not found.'), 'error');

            return;
        }

        $webhook->delete();
        $this->notify(__('Webhook deleted.'));
    }

    public function sendTest(int $id): void
    {
        $webhook = Webhook::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$webhook) {
            $this->notify(__('Webhook not found.'), 'error');

            return;
        }

        WebhookDispatchJob::dispatch(Auth::id(), 'webhook.test', [
            'message' => 'This is a test webhook from Paymenter.',
        ]);

        $this->notify(__('Test webhook dispatched.'));
    }

    public function dismissSecret(): void
    {
        $this->newSecret = null;
    }

    public function render()
    {
        return view('client.account.webhooks', [
            'webhooks'        => Webhook::where('user_id', Auth::id())->latest()->get(),
            'availableEvents' => self::AVAILABLE_EVENTS,
        ])->layoutData([
            'sidebar' => true,
            'title'   => 'Webhooks',
        ]);
    }
}
