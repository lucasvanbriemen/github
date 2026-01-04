@php
$routes = collect(Route::getRoutes())->map(function ($route) {
    return [
        'uri' => $route->uri(),
        'name' => $route->getName(),
        'method' => $route->methods()[0],
    ];
})->values();

$userID = \App\GithubConfig::USERID;

@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/js/main.js', 'resources/scss/components/global.scss'])
</head>
<body>
    <script>
        const API_ROUTES = @json($routes);
        window.USER_ID = "{{ $userID }}";

        function route(name, params = {}) {
            const route = API_ROUTES.find(r => r.name === name);
            let uri = route.uri;

            for (const [key, value] of Object.entries(params)) {
                // Remove the $ if present
                const cleanKey = key.startsWith('$') ? key.slice(1) : key;

                uri = uri.replace(`{${cleanKey}}`, encodeURIComponent(value));
            }

            return `/${uri}`;
        }
    </script>

    <div id="app"></div>
</body>
</html>
