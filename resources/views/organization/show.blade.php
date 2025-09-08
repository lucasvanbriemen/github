<x-app-layout class="organization-page">
  <x-slot:header>
    <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
    <h1>{{ $organization->name }}</h1>
  </x-slot>

  <div class="repositories">
    @foreach ($repositories as $repository)
      <div class="repository-card">
        <h2>{{ $repository->name }}</h2>
      </div>
    @endforeach
</x-app-layout>
