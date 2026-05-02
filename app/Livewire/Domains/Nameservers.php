<?php

namespace App\Livewire\Domains;

use App\Domains\Registrars\RegistrarFactory;
use App\Livewire\Component;
use App\Models\Domain;
use Exception;

class Nameservers extends Component
{
    public Domain $domain;

    public array $nameservers = ['', '', '', ''];

    public function mount(): void
    {
        try {
            $info = RegistrarFactory::for($this->domain)->getInfo($this->domain);
            $list = $info['nameservers'] ?? [];
            for ($i = 0; $i < 4; $i++) {
                $this->nameservers[$i] = $list[$i] ?? '';
            }
        } catch (Exception) {
            // Ignore — leave empty inputs.
        }
    }

    public function save(): void
    {
        try {
            $filtered = array_values(array_filter($this->nameservers, fn ($v) => trim((string) $v) !== ''));
            RegistrarFactory::for($this->domain)->setNameservers($this->domain, $filtered);
            $this->notify('Nameservers updated.', 'success');
        } catch (Exception $e) {
            $this->notify('Update failed: '.$e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('domains.nameservers')->layoutData([
            'title' => 'Nameservers — '.$this->domain->fqdn,
            'sidebar' => true,
        ]);
    }
}
