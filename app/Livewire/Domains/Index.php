<?php

namespace App\Livewire\Domains;

use App\Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?string $status = null;

    public function render()
    {
        $query = Auth::user()->domains()->with(['activeBinding.service'])->orderBy('expires_at');

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return view('domains.index', [
            'domains' => $query->paginate(config('settings.pagination', 15)),
        ])->layoutData([
            'title' => 'Domains',
            'sidebar' => true,
        ]);
    }
}
