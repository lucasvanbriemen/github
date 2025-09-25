<a href="{{ route('repository.prs.show', [$organization->name, $repository->name, $pullRequest->number]) }}" class="pullrequest-card card {{ $pullRequest->state }}">
  {!! svg('issue') !!}
  <div class='main-info'>
    <h3 class='issue-title'>
      {{ $pullRequest->title }}
    </h3>
    <span class='opened-by'>Opened by {{ $pullRequest->openedBy->name }} <img src="{{ $pullRequest->openedBy->avatar_url }}" alt="{{ $pullRequest->openedBy->name }}">{{ $pullRequest->created_at->diffForHumans() }}</span>
  </div>
</div>
</a>
