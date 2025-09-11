@foreach ($filecontent as $file)
  <a class="file"
     data-url="{{ route("repository.show", [$organization->name, $repository->name, $file->path, "isFile" => ($file->type === "file")]) }}"
     data-api-url="{{ route("api.repositories.show", [$organization->name, $repository->name, $file->path, "isFile" => ($file->type === "file")]) }}"
  >
    @if ($file->type === "dir")
      {!! svg("folder") !!}
    @elseif ($file->type === "file")
      {!! svg("file") !!}
    @endif
    <h2 class="name">{{ $file->name }}</h2>
  </a>
@endforeach
