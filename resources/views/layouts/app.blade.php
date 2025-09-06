<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ config("app.name", "Laravel") }}</title>

  <link rel="icon" href="{{ asset("images/favicon.png") }}" type="image/x-icon">

  <!-- Scripts -->
  @vite(["resources/scss/root.scss", "resources/js/root.js"])
</head>

<body class="{{ $class ?? "" }}">
  <header class="header">
    {{ $header ?? "" }}
  </header>

  <main>
    {{ $slot }}
  </main>
</body>

</html>
