<x-app-layout class="organization-page">
  <x-slot:header>
    <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
    <h1>{{ $organization->name }}</h1>
  </x-slot>
</x-app-layout>
