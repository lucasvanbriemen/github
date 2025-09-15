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
    <div class='markdown-body'><x-markdown theme="github-dark">{!! $issue->body !!}</x-markdown></div>

    <div class="timeline-section">
      <h3>Timeline</h3>
      @dump($issue->timeline)
    </div>
  </div>
</x-app-layout>
