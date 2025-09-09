<x-app-layout class="repository-page">
  <x-slot:header>
    @if ($organization)
      <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
    @endif
    <h1>{{ $repository->full_name }}</h1>
  </x-slot>

  <div class="navigation">
    <a href="{{ route('organization.show', $organization->name) }}">Back to {{ $organization->login }}</a>
  </div>

  @if (empty($filecontent))
    @include("repository.no_content")
  @else
    <div class="file-list">
      @foreach ($filecontent as $file)
        <div class="file">
          <h2 class="name">{{ $file->name }}</h2>
        </div>
      @endforeach
    </div>
  @endif
</x-app-layout>
