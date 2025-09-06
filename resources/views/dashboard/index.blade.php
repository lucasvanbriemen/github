<x-app-layout class="dashboard-page">
  <x-slot:header>
    <h1>{{ __("Dashboard") }}</h1>
  </x-slot>

  <div class="organizations">
    @foreach ($organizations as $organization)
      <a class="organization-card" href="{{ route("organization.show", $organization) }}">
        <img src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
        <h2 class="name">{{ $organization->name }}</h2>
      </a>
    @endforeach

    <button class="update-organizations">Update organizations</button>
  </div>
</x-app-layout>