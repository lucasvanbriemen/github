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
        <span class="author"><img src="{{ $issue->openedBy->avatar_url }}" alt="{{ $issue->openedBy->name }}"> {{ $issue->openedBy->name }}</span>
        <span class="created-at">{{ $issue->created_at->diffForHumans() }}</span>
      </div>
    </div>
    <div class='markdown-body'><x-markdown theme="github-dark">{!! $issue->body !!}</x-markdown></div>
  </div>
</x-app-layout>
