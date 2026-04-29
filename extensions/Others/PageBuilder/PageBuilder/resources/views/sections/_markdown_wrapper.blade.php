@php
if (!function_exists('pb_md')) {
    function pb_md($text) {
        return app('pbMarkdown')($text ?? '');
    }
}
@endphp
