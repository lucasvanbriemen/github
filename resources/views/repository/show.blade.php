<x-app-layout class="repository-page">
  <x-slot:header>
    @if ($organization)
      <a href="{{ route('organization.show', $organization->name) }}">
        <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
      </a>
    @endif
    <a href="{{ route('repository.show', [$organization->name, $repository->name]) }}"><h1>{{ $repository->full_name }}</h1></a>
  </x-slot>

  <div class="navigation">
    <a href="{{ route('organization.show', $organization->name) }}">Back to {{ $organization->login }}</a>
  </div>

  @if (empty($filecontent))
    @include("repository.no_content")
  @elseif (!$isFile)
    <div class="file-list">
      @foreach ($filecontent as $file)
        <a class="file"href="{{ route('repository.show', [$organization->name, $repository->name, $file->path, 'isFile' => ($file->type === "file")]) }}">
          <h2 class="name">{{ $file->name }}</h2>
        </a>
      @endforeach
    </div>
  @else
    <pre><code class="hljs">{!! $filecontent !!}</code></pre>
  @endif
</x-app-layout>
