@foreach ($issues as $issue)
  <div class="linked-issue issue-{{ $issue->state }}">
    <span class="icon">{!! svg('issue') !!}</span>
    <a href="{{ route('repository.issues.show', [$organizationName, $repositoryName, $issue->number]) }}">
      {{ $issue->title }}
    </a>
  </div>
@endforeach
