<x-app-layout class="repository-page pull-request-page">
  <x-slot:header>
    @if ($organization)
      <a href="{{ route('organization.show', $organization->name) }}">
        <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
      </a>
    @endif
    <a href="{{ route('repository.show', [$organization->name, $repository->name]) }}"><h1>{{ $repository->full_name }}</h1></a>
  </x-slot>

  @include("repository.sidebar")

  <div class="main-content">
    <div class="pull-request-content">
      <div class="pull-request-header">
        <span>{{ $pullRequest->title }}</span>
        <div class="opened-by">
          <span class="author"><img src="{{ $pullRequest->openedBy->avatar_url }}" alt="{{ $pullRequest->openedBy->name }}"> {{ $pullRequest->openedBy->name }}</span>
          <span class="created-at">{{ $pullRequest->created_at->diffForHumans() }}</span>
        </div>
      </div>
      <div class='markdown-body'><x-markdown theme="github-dark">{!! $pullRequest->body !!}</x-markdown></div>
    </div>

    @foreach ($pullRequest->comments as $comment)
      <div class="issue-comment {{ $comment->resolved ? 'resolved' : '' }}" data-comment="{{ $comment->github_id }}">
        <div class="comment-header">
          <span class="author"><img src="{{ $comment->author?->avatar_url }}" alt="{{ $comment->author?->name }}"> {{ $comment->author?->name }}</span>
          <span class="created-at">{{ $comment->created_at->diffForHumans() }}</span>
        </div>
        <div class='markdown-body'>
          <x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown>
          
          @if(!$comment->resolved)
            <button class="button-primary resolve-comment" data-url="{{ route('api.repositories.issues.comment.resolve', [$organization->name, $repository->name, $pullRequest->number, $comment->github_id]) }}" data-comment="{{ $comment->github_id }}">Mark as resolved</button>
          @else
            <button class="button-primary unresolve-comment" data-url="{{ route('api.repositories.issues.comment.unresolve', [$organization->name, $repository->name, $pullRequest->number, $comment->github_id]) }}" data-comment="{{ $comment->github_id }}">Mark as unresolved</button>
          @endif
        </div>
      </div>
    @endforeach
  </div>

  <div class="pull-request-details">
    <div class="pull-request-detail reviewers">
      <h3>Reviewers</h3>
      @foreach ($pullRequest->reviewers_data as $reviewer)
        <div class="reviewer state-{{ $reviewer->state }}">
          <span class='icon'>{!! svg($reviewer->state) !!}</span>
          <img src="{{ $reviewer->avatar_url }}" alt="{{ $reviewer->name }}">
          <span>{{ $reviewer->name }}</span>
        </div>
      @endforeach
    </div>

    <div class="pull-request-detail assignees">
      <h3>Assignees</h3>
      @foreach ($pullRequest->assignees_data as $assignee)
        <div class="assignee">
          <img src="{{ $assignee->avatar_url }}" alt="{{ $assignee->name }}">
          <span>{{ $assignee->name }}</span>
        </div>
      @endforeach
    </div>
  </div>
</x-app-layout>
