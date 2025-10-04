<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ config("app.name", "Laravel") }}</title>

  <link rel="icon" href="{{ asset("images/logo.svg") }}" type="image/x-icon">

  <!-- Scripts -->
  @vite(["resources/scss/root.scss", "resources/js/root.js"])
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
</head>

<body>

  <main class="{{ $class ?? "" }}">
    {{ $slot }}
  </main>
</body>

</html>
