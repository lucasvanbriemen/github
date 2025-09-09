<x-app-layout class="dashboard-page">
  <x-slot:header>
    @if ($organization)
      <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
    @endif
    <h1>{{ $repository->full_name }}</h1>
  </x-slot>

  @if (empty($filecontent))
    @include("repository.no_content")
  @else
    {{ $filecontent[0]->name }}
  @endif
</x-app-layout>
