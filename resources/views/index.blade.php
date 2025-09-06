{{ __("Hello world") }}

{{ truncate_text("This is a long text that needs to be truncated", 20) }}

@php
$token = config('services.github.access_token');
var_dump($token);

@endphp