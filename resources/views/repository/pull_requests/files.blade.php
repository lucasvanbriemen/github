<x-app-layout class="repository-page pull-request-page">
  @vite(['resources/scss/shared/custom-diff.scss', 'resources/scss/shared/hljs-theme.scss', 'resources/js/diff-highlight.js'])

  <x-slot:header>
    @if ($organization)
      <a href="{{ route('organization.show', $organization->name) }}">
        <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
      </a>
    @endif
    <a href="{{ route('repository.show', [$organization->name, $repository->name]) }}"><h1>{{ $repository->full_name }}</h1></a>
  </x-slot>

  <div class="main-content file-view">
    @include("repository.pull_requests.nav")

    <div id="diff-container" style="margin-top: 20px;">
      @include('repository.pull_requests.partials.diff', ['files' => $files, 'pullRequest' => $pullRequest])
    </div>
  </div>

  <script>
    window.start = "pull_request_files";
    window.pullRequestId = "{{ $pullRequest->number }}";
    window.repositoryName = "{{ $repository->name }}";
    window.organizationName = "{{ $organization->name }}";
  </script>
</x-app-layout>
