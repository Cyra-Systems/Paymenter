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
            'name'                => 'required|string|min:1|max:255',
            'selectedPermissions' => 'array',
            'rateLimit'           => 'nullable|integer|min:1|max:10000',
            'ipAddresses'         => 'nullable|string|max:1000',
        ];
    }

    public function create(): void
    {
        $this->validate();

        $ips = null;
        if (trim($this->ipAddresses) !== '') {
            $entries = array_values(array_filter(array_map('trim', explode(',', $this->ipAddresses))));
            foreach ($entries as $entry) {
                if (!filter_var($entry, FILTER_VALIDATE_IP) && !$this->isValidCidr($entry)) {
                    $this->addError('ipAddresses', "Invalid IP address or CIDR: [{$entry}]");
                    return;
                }
            }
            $ips = $entries;
        }

        $rawToken = Str::random(64);

        ApiKey::create([
            'name'         => $this->name,
            'token'        => hash('sha256', $rawToken),
            'type'         => 'user',
            'user_id'      => Auth::id(),
            'permissions'  => !empty($this->selectedPermissions) ? $this->selectedPermissions : null,
            'rate_limit'   => $this->rateLimit ?: null,
            'ip_addresses' => $ips,
            'enabled'      => true,
        ]);

        $this->newToken = $rawToken;
        $this->reset('name', 'selectedPermissions', 'rateLimit', 'ipAddresses');
        $this->notify(__('API key created. Copy your token now — it will not be shown again.'));
    }

    public function toggle(int $id): void
    {
        $key = ApiKey::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('type', 'user')
            ->first();

        if (!$key) {
            $this->notify(__('API key not found.'), 'error');
            return;
        }

        $key->enabled = !$key->enabled;
        $key->save();
        $this->notify($key->enabled ? __('API key enabled.') : __('API key disabled.'));
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

    private function isValidCidr(string $value): bool
    {
        if (!str_contains($value, '/')) {
            return false;
        }
        [$ip, $prefix] = explode('/', $value, 2);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        $isIPv4 = (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        $max    = $isIPv4 ? 32 : 128;
        return is_numeric($prefix) && (int) $prefix >= 0 && (int) $prefix <= $max;
    }

    public function render()
    {
        $permissionLabels = [];
        foreach (config('permissions.api.user', []) as $section => $perms) {
            foreach ($perms as $key => $label) {
                $permissionLabels["{$section}.{$key}"] = $label;
            }
        }

        return view('client.account.api-keys', [
            'keys'                => ApiKey::where('user_id', Auth::id())->where('type', 'user')->latest()->get(),
            'availablePermissions' => config('permissions.api.user', []),
            'permissionLabels'    => $permissionLabels,
        ])->layoutData([
            'sidebar' => true,
            'title'   => 'API Keys',
        ]);
    }
}
