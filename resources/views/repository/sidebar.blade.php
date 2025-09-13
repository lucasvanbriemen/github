<div class="sidebar">
  <a href="{{ route("repository.issues.show", [$organization->name, $repository->name]) }}" class="sidebar-item">Show issues <span>{{ $repository->openIssues->count()  }}</span></a>
  <a href="{{ route("organization.show", [$organization->name, $repository->name]) }}" class="sidebar-item">Show PR <span>{{ $repository->pr_count }}</span></a>
  <a href="{{ route("organization.show", $organization->name) }}" class="sidebar-item">Show org</a>
  <a href="{{ route("organization.show", $organization->name) }}" class="sidebar-item">Show org</a>
</div>
