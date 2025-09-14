<x-app-layout class="repository-page">
  <x-slot:header>
    @if ($organization)
      <a href="{{ route('organization.show', $organization->name) }}">
        <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
      </a>
    @endif
    <a href="{{ route('repository.show', [$organization->name, $repository->name]) }}"><h1>{{ $repository->full_name }}</h1></a>
  </x-slot>

  @include("repository.sidebar")

  <div class="issues-list">
    @include("repository.issue_filter")

    @foreach ($issues as $issue)
      @include("repository.issue_card")
    @endforeach

    {{ $issues->links() }}
  </div>
  <script>window.start = "search_select";</script>
</x-app-layout>
