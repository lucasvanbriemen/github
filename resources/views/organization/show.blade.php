<x-app-layout class="organization-page">
  <x-slot:header>
    <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
    <h1>{{ $organization->name }}</h1>
  </x-slot>

  <div class="repositories">
    @foreach ($repositories as $repository)
      <a class="repository-card card" href="{{ route("repository.show", $repository->name) }}">
        <h2>{{ $repository->name }}</h2>
      </a>
    @endforeach
</x-app-layout>
