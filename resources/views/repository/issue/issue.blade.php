<x-app-layout class="repository-page issue-page">
  <x-slot:header>
    @if ($organization)
      <a href="{{ route('organization.show', $organization->name) }}">
        <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
      </a>
    @endif
    <a href="{{ route('repository.show', [$organization->name, $repository->name]) }}"><h1>{{ $repository->full_name }}</h1></a>
  </x-slot>

  @include("repository.sidebar")

  <div class="issue-list">
    <div class="issue-header">
      <span>{{ $issue->title }}</span>
      <div class="opened-by">
        <span class="author"><img src="{{ $issue->opened_by_image }}" alt="{{ $issue->opened_by }}"> {{ $issue->opened_by }}</span>
        <span class="created-at">{{ $issue->created_at->diffForHumans() }}</span>
      </div>
    </div>
    <div class='markdown-body'><x-markdown theme="github-dark">{!! $issue->body !!}</x-markdown></div>

    @if (count($timeline))
      <div class="timeline-section">
        <h3>Timeline</h3>
        @foreach ($timeline as $event)
          {{-- {!! timelineView($event, $event->data, $event->actor, $issue) !!} --}}
        @endforeach
      </div>
    @endif
  </div>
</x-app-layout>
