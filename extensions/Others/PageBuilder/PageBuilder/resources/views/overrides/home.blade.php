@php
    try {
        $pageBuilderPage = \Paymenter\Extensions\Others\PageBuilder\Models\Page::where('route', '/')->first();
        
        if ($pageBuilderPage && isset($sections) && !empty($sections)) {
            echo view('pagebuilder::page-sections', [
                'sections' => $sections,
                'colors' => $colors ?? [],
                'hpbCssVariables' => $hpbCssVariables ?? '',
            ])->render();
        } else {
            $theme = config('settings.theme');
            $themePath = base_path('themes/' . $theme . '/views/home.blade.php');
            if (file_exists($themePath)) {
                $allVars = get_defined_vars();
                unset($allVars['__data'], $allVars['__path']);
                echo \Illuminate\Support\Facades\View::file($themePath, $allVars)->render();
            }
        }
    } catch (\Exception $e) {
        $theme = config('settings.theme');
        $themePath = base_path('themes/' . $theme . '/views/home.blade.php');
        if (file_exists($themePath)) {
            $allVars = get_defined_vars();
            unset($allVars['__data'], $allVars['__path']);
            echo \Illuminate\Support\Facades\View::file($themePath, $allVars)->render();
        }
    }
@endphp

