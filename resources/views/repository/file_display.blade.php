@if (empty($filecontent))
  @include("repository.no_content")
@elseif (!$isFile)
  @include("repository.file_navigation")
@else
  <pre>{!! $filecontent !!}</pre>
@endif