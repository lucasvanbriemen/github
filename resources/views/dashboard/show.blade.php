<x-app-layout class="dashboard-page">
  <x-slot:header>
    <h1>{{ __("Dashboard") }}</h1>
  </x-slot>

  <div class="organizations">
    @foreach ($organizations as $organization)
      <a class="organization-card card" href="{{ route("organization.show", $organization->name) }}">
        <img src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
        <h2 class="name">{{ $organization->name }}</h2>
      </a>
    @endforeach

    <button class="button-primary update-organizations">{{ __("Update organizations") }}</button>
  </div>

  <div class="repositories">
    @foreach ($repositories as $repository)
      <a class="repository-card card" href="{{ route("repository.show", [$organization->name, $repository->name]) }}">
        <h2 class="name">{{ $repository->full_name }}</h2>
      </a>
    @endforeach

    <button class="button-primary update-repositories">{{ __("Update repositories") }}</button>
  </div>

  <script>window.start = "dashboard";</script>
</x-app-layout>