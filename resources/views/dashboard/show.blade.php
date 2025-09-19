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
      <div class="issue-card card">
        <h3 class="title">
          <a href="{{ $issue->html_url }}" target="_blank" rel="noopener noreferrer">
            {{ $issue->title }}
          </a>
        </h3>
        <p class="state">State: {{ ucfirst($issue->state) }}</p>
        <p class="updated-at">Updated at: {{ $issue->updated_at->format('Y-m-d H:i') }}</p>
      </div>
    @endforeach
  </div>

  <script>window.start = "dashboard";</script>
</x-app-layout>