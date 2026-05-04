<footer class="footer-rail w-full px-4 py-4 lg:mt-72 mt-44">
    <div class="container my-12 mx-auto px-4 sm:px-6 md:px-8 lg:px-10">
        <div class="flex flex-col md:flex-row justify-between gap-2 items-center">
            <div class="flex flex-col gap-6 items-start">
                @php($logoDisplay = theme('logo_display', 'logo-and-name'))
                @if ($logoDisplay !== 'none')
                <div class="flex flex-row gap-2">
                    @if (in_array($logoDisplay, ['logo', 'logo-only', 'logo-and-name']))
                    <x-logo class="h-10" />
                    @endif
                    @if (in_array($logoDisplay, ['text', 'logo-and-name']))
                    <span class="text-xl font-bold leading-none flex items-center">{{ config('app.name') }}</span>
                    @endif
                </div>
                @endif
                <div class="text-sm text-base/70">
                    {{ __('© :year :app_name. | All rights reserved.', ['year' => date('Y'), 'app_name' => config('app.name')]) }}
                </div>
            </div>
        </div>
    </div>
</footer>
