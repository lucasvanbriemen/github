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

  <div class="pullrequest-list">
    @include("repository.pull_requests.partials.create_notice")
    @include("repository.issue.header")


    <div class="pullrequest-wrapper">
    </div>
  </div>

  <script>
    window.start = ["search_select", "pull_requests"];
    window.organizationName = "{{ $organization->name }}";
    window.repositoryName = "{{ $repository->name }}";
  </script>
</x-app-layout>
