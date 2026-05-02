<?php

namespace App\Livewire\Domains;

use App\Domains\Registrars\RegistrarFactory;
use App\Livewire\Component;
use App\Models\Domain;
use Exception;
use Livewire\Attributes\Locked;

class Show extends Component
{
    public Domain $domain;

    #[Locked]
    public array $info = [];

    public function mount(): void
    {
        $this->refreshInfo();
    }

    public function refreshInfo(): void
    {
        try {
            $registrar = RegistrarFactory::for($this->domain);
            $this->info = $registrar->getInfo($this->domain);
            $this->domain->update([
                'expires_at' => $this->info['expires_at'] ?? $this->domain->expires_at,
                'auth_code' => $this->info['auth_code'] ?? $this->domain->auth_code,
                'locked' => $this->info['locked'] ?? $this->domain->locked,
                'last_synced_at' => now(),
            ]);
            $this->domain->refresh();
        } catch (Exception $e) {
            $this->notify('Could not refresh from registrar: '.$e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('domains.show', [
            'binding' => $this->domain->activeBinding()->with('service.product')->first(),
        ])->layoutData([
            'title' => $this->domain->fqdn,
            'sidebar' => true,
        ]);
    }
}
