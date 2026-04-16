@php
$routes = collect(Route::getRoutes())->map(function ($route) {
    return [
        'uri' => $route->uri(),
        'name' => $route->getName(),
        'method' => $route->methods()[0],
    ];
})->values();

$userID = \App\GithubConfig::USERID;
$username = \App\GithubConfig::USERNAME;
$orgRules = \App\GithubConfig::ORG_RULES;

@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <style>
        #native-launch-splash {
            position: fixed;
            inset: 0;
            z-index: 2147483647;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0d1117;
            color: #e6edf3;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        #native-launch-splash .nls-card {
            max-width: 420px;
            padding: 2rem 2.25rem;
            text-align: center;
        }
        #native-launch-splash h1 {
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }
        #native-launch-splash p {
            margin: 0 0 1.5rem;
            color: #8b949e;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        #native-launch-splash .nls-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        #native-launch-splash button {
            font: inherit;
            padding: 0.625rem 1rem;
            border-radius: 6px;
            border: 1px solid transparent;
            cursor: pointer;
        }
        #native-launch-splash .nls-primary {
            background: #238636;
            color: #fff;
        }
        #native-launch-splash .nls-primary:hover { background: #2ea043; }
        #native-launch-splash .nls-secondary {
            background: transparent;
            border-color: #30363d;
            color: #e6edf3;
        }
        #native-launch-splash .nls-secondary:hover { background: #161b22; }
        #native-launch-splash .nls-remember {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            color: #8b949e;
            font-size: 0.85rem;
        }
    </style>
    @vite(['resources/js/main.js', 'resources/scss/components/global.scss'])
</head>
<body>
    <script>
        const API_ROUTES = @json($routes);
        window.USER_ID = "{{ $userID }}";
        window.CURRENT_USER_LOGIN = "{{ $username }}";
        window.ORG_RULES = @json($orgRules);
        window.ABLY_SUB_KEY = "{{ config('services.ably.sub_key') }}";

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

    <script>
    (function () {
        var NATIVE_HOSTS = ['github.lucasvanbriemen.nl'];
        var STORAGE_KEY = 'gh_gui_prefer_browser';
        var PROTOCOL = 'githubgui';

        if (window.electronAPI) return;
        if (NATIVE_HOSTS.indexOf(window.location.hostname) === -1) return;

        try {
            if (localStorage.getItem(STORAGE_KEY) === '1') return;
        } catch (e) {}

        var rel = window.location.pathname + window.location.search + window.location.hash;
        var protocolUrl = PROTOCOL + '://open?u=' + encodeURIComponent(rel);

        var splash = document.createElement('div');
        splash.id = 'native-launch-splash';
        splash.innerHTML = ''
            + '<div class="nls-card">'
            +   '<h1>Opening GitHub GUI</h1>'
            +   '<p>Launching the desktop app. If nothing happens, install the app or continue in your browser.</p>'
            +   '<div class="nls-actions">'
            +     '<button type="button" class="nls-primary" id="nls-open">Open in desktop app</button>'
            +     '<button type="button" class="nls-secondary" id="nls-browser">Continue in browser</button>'
            +   '</div>'
            +   '<label class="nls-remember"><input type="checkbox" id="nls-remember"> Remember my choice</label>'
            + '</div>';
        document.body.appendChild(splash);

        function launch() {
            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = protocolUrl;
            document.body.appendChild(iframe);
            setTimeout(function () {
                if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
            }, 2000);
        }

        function dismiss() {
            if (document.getElementById('nls-remember').checked) {
                try { localStorage.setItem(STORAGE_KEY, '1'); } catch (e) {}
            }
            splash.parentNode && splash.parentNode.removeChild(splash);
        }

        document.getElementById('nls-open').addEventListener('click', launch);
        document.getElementById('nls-browser').addEventListener('click', dismiss);

        launch();
    })();
    </script>
</body>
</html>
