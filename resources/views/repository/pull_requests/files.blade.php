<x-app-layout class="repository-page pull-request-page">
  <x-slot:header>
    @if ($organization)
      <a href="{{ route('organization.show', $organization->name) }}">
        <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
      </a>
    @endif
    <a href="{{ route('repository.show', [$organization->name, $repository->name]) }}"><h1>{{ $repository->full_name }}</h1></a>
  </x-slot>

  <div class="main-content">
    @include("repository.pull_requests.nav")

    <div id="diff-container" style="margin-top: 20px;"></div>
  </div>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/diff2html@3.4.52/bundles/css/diff2html.min.css">
  <script src="https://cdn.jsdelivr.net/npm/diff2html@3.4.52/bundles/js/diff2html-ui.min.js"></script>

  <script>
    window.start = "pull_request_files";
    window.pullRequestId = "{{ $pullRequest->number }}";
    window.repositoryName = "{{ $repository->name }}";
    window.organizationName = "{{ $organization->name }}";

    document.addEventListener('DOMContentLoaded', function() {
      const diffString = @json($diff);

      const targetElement = document.getElementById('diff-container');
      const configuration = {
        drawFileList: true,
        fileListToggle: false,
        fileListStartVisible: false,
        fileContentToggle: false,
        matching: 'lines',
        outputFormat: 'side-by-side',
        synchronisedScroll: true,
        highlight: true,
        renderNothingWhenEmpty: false,
      };

      const diff2htmlUi = new Diff2HtmlUI(targetElement, diffString, configuration);
      diff2htmlUi.draw();
      diff2htmlUi.highlightCode();
    });
  </script>
</x-app-layout>
