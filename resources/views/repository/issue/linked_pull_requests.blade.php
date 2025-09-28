<h3>Linked Pull Requests</h3>
@foreach ($pullRequests as $pullRequest)
  <div class="linked-pull-request pull-request-{{ $pullRequest->state }}">
    <span class="icon">{!! svg('pull-request') !!}</span>
    <a href="{{ route('repository.prs.show', [$organizationName, $repositoryName, $pullRequest->number]) }}">
      {{ $pullRequest->title }}
    </a>
  </div>
@endforeach
