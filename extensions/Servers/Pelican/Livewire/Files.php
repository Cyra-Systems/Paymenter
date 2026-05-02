<?php

namespace Paymenter\Extensions\Servers\Pelican\Livewire;

use App\Models\Service;
use Exception;

class Files extends Component
{
    public ?array $panelServer = null;
    public string $directory   = '/';
    public array  $entries     = [];
    public ?string $editingFile = null;
    public string $editorContents = '';
    public ?string $error      = null;

    public function mount(Service $service): void
    {
        parent::mount($service);
        $this->requireToggle('show_files');

        $this->panelServer = $this->panelServer();
        $this->refresh();
    }

    public function refresh(): void
    {
        if (! $this->panelServer) return;

        $this->error = null;
        try {
            $resp = $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/files/list',
                'get',
                ['directory' => $this->directory]
            );
            $this->entries = array_map(
                fn($e) => $e['attributes'] ?? $e,
                $resp['data'] ?? []
            );
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->entries = [];
        }
    }

    public function navigate(string $name, bool $isDir): void
    {
        if (! $isDir) {
            $this->openFile(rtrim($this->directory, '/') . '/' . $name);
            return;
        }
        $this->directory = rtrim($this->directory, '/') . '/' . $name;
        $this->refresh();
    }

    public function up(): void
    {
        if ($this->directory === '/' || $this->directory === '') return;
        $this->directory = '/' . trim(dirname($this->directory), '/');
        if ($this->directory === '/.') $this->directory = '/';
        $this->refresh();
    }

    public function openFile(string $path): void
    {
        if (! $this->panelServer) return;
        try {
            $resp = $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/files/contents',
                'get',
                ['file' => $path]
            );
            $this->editingFile    = $path;
            $this->editorContents = is_array($resp) ? json_encode($resp, JSON_PRETTY_PRINT) : (string) $resp;
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function saveFile(): void
    {
        if (! $this->panelServer || $this->editingFile === null) return;
        try {
            $this->pelican()->clientRequestRaw(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/files/write?file=' . urlencode($this->editingFile),
                $this->editorContents
            );
            $this->notify('File saved.', 'success');
            $this->closeEditor();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function closeEditor(): void
    {
        $this->editingFile    = null;
        $this->editorContents = '';
    }

    public function deleteEntry(string $name): void
    {
        if (! $this->panelServer) return;
        try {
            $this->pelican()->clientRequest(
                '/api/client/servers/' . $this->panelServer['uuid'] . '/files/delete',
                'post',
                ['root' => $this->directory, 'files' => [$name]]
            );
            $this->notify('Deleted: ' . $name, 'success');
            $this->refresh();
        } catch (Exception $e) {
            $this->notify($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        return view('pelican::tabs.files');
    }
}
