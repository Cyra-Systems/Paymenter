<?php

namespace App\Livewire\Domains;

use App\Domains\Registrars\EnomRegistrar;
use App\Domains\Services\DomainProvisioningService;
use App\Livewire\Component;
use App\Models\DomainTld;
use Exception;
use Illuminate\Support\Facades\Auth;

class Register extends Component
{
    public string $sld = '';

    public string $tld = '';

    public int $years = 1;

    public bool $idProtect = false;

    public bool $autoRenew = true;

    public ?string $availability = null;

    public function rules(): array
    {
        return [
            'sld' => ['required', 'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i'],
            'tld' => ['required', 'string', 'max:63'],
            'years' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function check(): void
    {
        $this->validate();

        $this->tld = strtolower(ltrim(trim($this->tld), '.'));
        $tldRecord = DomainTld::query()->where('tld', $this->tld)->where('enabled', true)->first();
        if (! $tldRecord) {
            $this->availability = "We don't support .{$this->tld} right now.";

            return;
        }

        try {
            $registrar = new EnomRegistrar;
            $available = $registrar->check($this->sld, $this->tld);
            $this->availability = $available
                ? sprintf('%s.%s is available — %s%.2f/yr', $this->sld, $this->tld, $tldRecord->currency_code.' ', $tldRecord->priceWithMargin('register_price'))
                : sprintf('%s.%s is taken.', $this->sld, $this->tld);
        } catch (Exception $e) {
            $this->availability = 'Lookup failed: '.$e->getMessage();
        }
    }

    public function register(): void
    {
        $this->validate();

        try {
            $domain = app(DomainProvisioningService::class)->register(
                Auth::user(),
                $this->sld,
                $this->tld,
                $this->years,
                ['id_protect' => $this->idProtect, 'auto_renew' => $this->autoRenew],
            );

            $this->notify('Domain '.$domain->fqdn.' registered.', 'success');
            $this->redirect(route('domains.show', $domain), true);
        } catch (Exception $e) {
            $this->notify('Registration failed: '.$e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('domains.register', [
            'tlds' => DomainTld::query()->where('enabled', true)->orderBy('display_order')->get(),
        ])->layoutData([
            'title' => 'Register a Domain',
            'sidebar' => true,
        ]);
    }
}
