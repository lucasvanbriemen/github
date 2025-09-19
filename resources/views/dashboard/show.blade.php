<x-app-layout class="dashboard-page">
  <x-slot:header>
    <h1>{{ __("Dashboard") }}</h1>
  </x-slot>

  <div class="user-profile">
    <img src="{{ gravatar() }}" alt="{{ currentUser()->name }}" class="avatar">
    <h2 class="name">{{ currentUser()->name }}</h2>
  </div>

  <div class="repositories">
    @foreach ($repositories as $repository)
      @php $owner = explode("/", $repository->full_name)[0] ?? "user"; @endphp
      <a class="repository-card card" href="{{ route("repository.show", [$owner, $repository->name]) }}">
        <img class="avatar" src="{{ $repository->organization->avatar_url }}" alt="{{ $repository->full_name }}">
        <h2 class="name">{{ $repository->full_name }}</h2>
      </a>
    @endforeach
  </div>

  <div class="issues">
    @foreach ($issues as $issue)
      <a class="issue-card card" href="{{ route("repository.issues.show", [$owner, $issue->repository->name, $issue->number]) }}">
        <div>
          <img class="avatar" src="{{ $issue->repository->organization->avatar_url }}" alt="{{ $issue->repository->full_name }}">
          <h3 class="title">{{ $issue->title }}</h3>
        </div>
        <p class="updated-at">{{ $issue->updated_at->diffForHumans() }}</p>
      </a>
    @endforeach
  </div>

  <script>window.start = "dashboard";</script>
</x-app-layout>