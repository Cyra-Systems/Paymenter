<?php
namespace Paymenter\Extensions\Servers\DownloadableProducts\Admin\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Paymenter\Extensions\Servers\DownloadableProducts\Models\DownloadLog;

class DownloadLogs extends Page
{
    protected static ?string $navigationLabel = 'Download Logs';
    protected static \BackedEnum|string|null $navigationIcon = 'ri-file-paper-2-line';
    protected static \UnitEnum|string|null $navigationGroup = 'Downloadable Products';

    protected string $view = 'downloadableproducts::admin.download-logs';

    public $logs;

    public function mount(): void
    {
        $this->loadLogs();
    }

    public function clearLogs()
    {
        try {
            DownloadLog::truncate();
            $this->loadLogs();
            
            Notification::make()
                ->title('Success')
                ->body('All download logs have been cleared successfully.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to clear download logs: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function loadLogs(): void
    {
        $this->logs = DownloadLog::with('service', 'user')->latest()->get();
    }
}