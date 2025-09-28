@foreach ($issues as $issue)
  @include("repository.issue.issue_card")
@endforeach

{{ $issues->links() }}
