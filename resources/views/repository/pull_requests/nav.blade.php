<nav class="pull-request-nav">
  <a href='{{ route("repository.prs.show", [$organization->name, $repository->name, $pullRequest->number]) }}' class=" {{ Route::currentRouteName() === "repository.prs.show" ? "active" : "" }}">Conversation</a>
  <a href='{{ route("repository.prs.show.files", [$organization->name, $repository->name, $pullRequest->number]) }}' class=" {{ Route::currentRouteName() === "repository.prs.show.files" ? "active" : "" }}">Files</a>
</nav>
