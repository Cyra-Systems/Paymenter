<?php

namespace App\Livewire\Client;

use App\Livewire\Component;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

class ApiKeys extends Component
{
    public string $name = '';

    public array $selectedPermissions = [];

    public ?int $rateLimit = null;

    public string $ipAddresses = '';

    #[Locked]
    public ?string $newToken = null;

    public function rules(): array
    {
        return [
            'name'               => 'required|string|min:1|max:255',
            'selectedPermissions' => 'array',
            'rateLimit'          => 'nullable|integer|min:1|max:10000',
            'ipAddresses'        => 'nullable|string|max:1000',
        ];
    }

    public function create(): void
    {
        $this->validate();

        $rawToken = Str::random(64);

        $ips = null;
        if ($this->ipAddresses) {
            $ips = array_values(array_filter(array_map('trim', explode(',', $this->ipAddresses))));
        }

        ApiKey::create([
            'name'        => $this->name,
            'token'       => hash('sha256', $rawToken),
            'type'        => 'user',
            'user_id'     => Auth::id(),
            'permissions' => !empty($this->selectedPermissions) ? $this->selectedPermissions : null,
            'rate_limit'  => $this->rateLimit ?: null,
            'ip_addresses' => $ips,
            'enabled'     => true,
        ]);

        $this->newToken = $rawToken;
        $this->reset('name', 'selectedPermissions', 'rateLimit', 'ipAddresses');
        $this->notify(__('API key created. Copy your token now — it will not be shown again.'));
    }

    public function revoke(int $id): void
    {
        $key = ApiKey::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('type', 'user')
            ->first();

        if (!$key) {
            $this->notify(__('API key not found.'), 'error');

            return;
        }

        $key->delete();
        $this->notify(__('API key revoked.'));
    }

    public function dismissToken(): void
    {
        $this->newToken = null;
    }

    public function render()
    {
        return view('client.account.api-keys', [
            'keys'                => ApiKey::where('user_id', Auth::id())->where('type', 'user')->latest()->get(),
            'availablePermissions' => config('permissions.api.user', []),
        ])->layoutData([
            'sidebar' => true,
            'title'   => 'API Keys',
        ]);
    }
}
