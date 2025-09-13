<a href="{{ route('repository.issues.show', [$organization->name, $repository->name, $issue->number]) }}" class="issue-card card">
  {{ $issue->title }} - state: {{ $issue->state }}
</a>
