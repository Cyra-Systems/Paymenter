@php
    $html = data_get($data, 'content.html');
    $css = data_get($data, 'content.css');
@endphp

<section class="py-20 relative">
    <div class="container mx-auto max-w-[1320px] px-4">
        @if(!empty($css))
            <style>{!! $css !!}</style>
        @endif
        {!! $pbMarkdown($html) !!}
    </div>
    
</section>


