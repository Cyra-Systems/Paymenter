<?php

namespace Paymenter\Extensions\Servers\DownloadableProducts;

use App\Classes\Extension\Server;
use App\Models\Service;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;
use Paymenter\Extensions\Servers\DownloadableProducts\Models\DownloadLog;

class DownloadableProducts extends Server
{
    #[ExtensionMeta(
        name: 'Downloadable Products',
        description: 'A free to use extension to sell downloadable files with optimal security',
        version: '1.3',
        author: 'QKing',
        url: 'https://host4you.cloud',
        icon: 'https://paymenter.org/logo-dark.svg'
    )]


    public function boot()
    {
        require __DIR__ . '/routes.php';
        View::addNamespace('downloadableproducts', __DIR__ . '/resources/views');
    }

    public function installed()
    {
        ExtensionHelper::runMigrations('extensions/Servers/DownloadableProducts/database/migrations');
    }

    public function getConfig($values = []): array
    {
        return [
            [
                'name' => 'Notice',
                'type' => 'placeholder',
                'label' => 'You can use this extension to manage downloadable products.',
            ],
        ];
    }

    public function getProductConfig($values = []): array
    {
        return [
            [
                'name' => 'file_upload',
                'label' => 'File Upload',
                'type' => 'file',
                'description' => 'Upload the file for this product.',
                'required' => true,
                'disk' => 'local',
                'preserve_filenames' => true,
            ],
            [
                'name' => 'download_limit',
                'label' => 'Download Limit',
                'type' => 'number',
                'description' => 'Maximum number of times the customer can download this file. Leave empty or 0 for unlimited.',
                'default' => 0,
            ],
            [
                'name' => 'download_expiry',
                'label' => 'Download Expiry (days)',
                'type' => 'number',
                'description' => 'Number of days after purchase the download is available. Leave empty or 0 for no expiry.',
                'default' => 0,
            ],
        ];
    }

    public function createServer(Service $service, $settings, $properties)
    {
        return true;
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function getActions(Service $service, $settings, $properties): array
    {
        return [
            ['type' => 'button', 'label' => 'Download', 'function' => 'download'],
            ['type' => 'view', 'name' => 'Download', 'label' => 'Download File']
        ];
    }

    public function getView(Service $service, $settings, $properties, $view)
    {
        $settingsArray = is_object($settings) ? (array) $settings : $settings;

        if (!empty($settingsArray['file_upload'])) {
            $filePath = storage_path('app/' . $settingsArray['file_upload']);
            if (file_exists($filePath)) {
                $settingsArray['file_checksum'] = hash_file('sha256', $filePath);
            }
        }

        return view('downloadableproducts::overview', [
            'service' => $service,
            'settings' => $settingsArray,
        ]);
    }

    public function download(Service $service, $settings, $properties)
    {
        $fileUpload = $settings['file_upload'] ?? null;
        $downloadLimit = (int)($settings['download_limit'] ?? 0);
        $expiryDays = (int)($settings['download_expiry'] ?? 0);

        if (!$fileUpload) {
            session()->flash('error', 'File upload is not set for this product.');
            return redirect()->back();
        }

        if ($expiryDays > 0) {
            $expiryDate = $service->created_at->addDays($expiryDays);
            if (now()->greaterThan($expiryDate)) {
                session()->flash('error', 'Download period has expired for this product.');
                return redirect()->back();
            }
        }

        if ($downloadLimit > 0 && $service->download_count >= $downloadLimit) {
            session()->flash('error', 'Download limit reached for this product.');
            return redirect()->back();
        }

        $filePath = storage_path('app/' . $fileUpload);
        if (!file_exists($filePath)) {
            session()->flash('error', 'File not found.');
            return redirect()->back();
        }

        $service->increment('download_count');

        DownloadLog::create([
            'service_id' => $service->id,
            'file_name' => basename($fileUpload),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
        ]);

        return response()->download($filePath, basename($filePath), ['Content-Type' => 'application/octet-stream']);
    }
}
