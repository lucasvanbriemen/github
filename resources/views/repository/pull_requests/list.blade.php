@foreach ($pullRequests as $pullRequest)
  @include("repository.pull_requests.pull_request_card")
@endforeach

{{ $pullRequests->links() }}
