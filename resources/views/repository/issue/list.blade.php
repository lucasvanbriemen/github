@foreach ($issues as $issue)
  @include("repository.issue_card")
@endforeach

{{ $issues->links() }}
