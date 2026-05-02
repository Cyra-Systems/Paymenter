<?php

namespace App\Livewire\Domains;

use App\Domains\Services\DomainProvisioningService;
use App\Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Auth;

class Transfer extends Component
{
    public string $fqdn = '';

    public string $authCode = '';

    public function rules(): array
    {
        return [
            'fqdn' => ['required', 'regex:/^[a-z0-9][a-z0-9-]*\.[a-z0-9][a-z0-9.-]*$/i'],
            'authCode' => ['required', 'string', 'min:6'],
        ];
    }

    public function transfer(): void
    {
        $this->validate();

        try {
            $domain = app(DomainProvisioningService::class)->transfer(Auth::user(), strtolower($this->fqdn), $this->authCode);
            $this->notify('Transfer initiated for '.$domain->fqdn.'. The registrar will email you for approval.', 'success');
            $this->redirect(route('domains.show', $domain), true);
        } catch (Exception $e) {
            $this->notify('Transfer failed: '.$e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('domains.transfer')->layoutData([
            'title' => 'Transfer a Domain',
            'sidebar' => true,
        ]);
    }
}
