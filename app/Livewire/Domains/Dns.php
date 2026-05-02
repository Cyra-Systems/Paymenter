<?php

namespace App\Livewire\Domains;

use App\Domains\Registrars\RegistrarFactory;
use App\Livewire\Component;
use App\Models\Domain;
use Exception;

class Dns extends Component
{
    public Domain $domain;

    public array $records = [];

    public array $nameservers = [];

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        try {
            $payload = RegistrarFactory::for($this->domain)->getDns($this->domain);
            $this->records = $payload['records'] ?? [];
            $this->nameservers = $payload['nameservers'] ?? [];
        } catch (Exception $e) {
            $this->notify('Could not load DNS records: '.$e->getMessage(), 'error');
        }
    }

    public function addRecord(): void
    {
        $this->records[] = ['hostname' => '@', 'type' => 'A', 'address' => '', 'priority' => null];
    }

    public function removeRecord(int $index): void
    {
        unset($this->records[$index]);
        $this->records = array_values($this->records);
    }

    public function save(): void
    {
        try {
            $payload = RegistrarFactory::for($this->domain)->setDns($this->domain, $this->records);
            $this->records = $payload['records'] ?? $this->records;
            $this->notify('DNS records saved.', 'success');
        } catch (Exception $e) {
            $this->notify('Save failed: '.$e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('domains.dns')->layoutData([
            'title' => 'DNS — '.$this->domain->fqdn,
            'sidebar' => true,
        ]);
    }
}
