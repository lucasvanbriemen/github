<x-app-layout class="dashboard-page">
  <x-slot:header>
    <h1>{{ __("Dashboard") }}</h1>
  </x-slot>

  <div class="organizations">
    @foreach ($organizations as $organization)
      <div class="organization-card">
        <img src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
        <h2>{{ $organization->name }}</h2>
        <p>{{ $organization->description }}</p>
      </div>
    @endforeach
  </div>
</x-app-layout>