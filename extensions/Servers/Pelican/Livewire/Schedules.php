<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;

class Schedules extends Component
{
    public ?array $panelServer = null;
    public array  $schedules   = [];
    public string $newName     = '';
    public string $newMinute   = '*';
    public string $newHour     = '*';
    public string $newDayOfWeek = '*';
    public string $newDayOfMonth = '*';
    public bool   $newOnlyWhenOnline = true;

    public function mount(Service $service): void
    {
        parent::mount($service);
        $this->requireToggle('show_schedules');

        $this->panelServer = $this->panelServer();
        $this->refresh();
    }

    public function refresh(): void
    {
        if (! $this->panelServer) return;
        try {
            $resp = $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/schedules'
            );
            $this->schedules = array_map(fn($s) => $s['attributes'] ?? $s, $resp['data'] ?? []);
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function create(): void
    {
        if (! $this->panelServer || trim($this->newName) === '') return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/schedules',
                'post',
                [
                    'name'             => $this->newName,
                    'minute'           => $this->newMinute,
                    'hour'             => $this->newHour,
                    'day_of_week'      => $this->newDayOfWeek,
                    'day_of_month'     => $this->newDayOfMonth,
                    'is_active'        => true,
                    'only_when_online' => $this->newOnlyWhenOnline,
                ]
            );
            $this->newName = '';
            $this->notify('Schedule created.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function execute(int $id): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/schedules/' . $id . '/execute',
                'post'
            );
            $this->notify('Schedule triggered.', 'success');
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function delete(int $id): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/schedules/' . $id,
                'delete'
            );
            $this->notify('Schedule deleted.', 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('pelican::tabs.schedules');
    }
}
