<div class="file-list">
  @foreach ($filecontent as $file)
    <a class="file"href="{{ route("repository.show", [$organization->name, $repository->name, $file->path, "isFile" => ($file->type === "file")]) }}">

      @if ($file->type === "dir")
        {!! svg("folder") !!}
      @elseif ($file->type === "file")
        {!! svg("file") !!}
      @endif

      <h2 class="name">{{ $file->name }}</h2>
    </a>
  @endforeach
</div>
