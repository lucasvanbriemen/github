<x-app-layout class="dashboard-page">
  <x-slot:header>
    <h1>{{ __("Dashboard") }}</h1>
  </x-slot>

  <img src='{{ gravatar() }}' alt='{{ currentUser()->name }}' class='avatar'>

  <div class="repositories">
    @foreach ($repositories as $repository)
      @php $owner = explode('/', $repository->full_name)[0] ?? 'user'; @endphp
      <a class="repository-card card" href="{{ route('repository.show', [$owner, $repository->name]) }}">
        <h2 class="name">{{ $repository->full_name }}</h2>
      </a>
    @endforeach
  </div>

  <script>window.start = "dashboard";</script>
</x-app-layout>