<x-app-layout class="repository-page">
  <x-slot:header>
    @if ($organization)
      <a href="{{ route('organization.show', $organization->name) }}">
        <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
      </a>
    @endif
    <a href="{{ route('repository.show', [$organization->name, $repository->name]) }}"><h1>{{ $repository->full_name }}</h1></a>
  </x-slot>

  <div class="navigation">
    <a href="{{ route('organization.show', $organization->name) }}">Back to {{ $organization->login }}</a>
  </div>

  @if (empty($filecontent))
    @include("repository.no_content")
  @elseif (!$isFile)
    @include("repository.file_navigation")
  @else
    <div class="file-list"><pre>{!! $filecontent !!}</pre></div>
  @endif
</x-app-layout>
