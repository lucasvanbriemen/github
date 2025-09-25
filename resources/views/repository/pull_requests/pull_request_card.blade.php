<a href="{{ route('repository.prs.show', [$organization->name, $repository->name, $pullRequest->number]) }}" class="pullrequest-card card {{ $pullRequest->state }}">
  {!! svg('pull_request') !!}
  <div class='main-info'>
    <h3 class='issue-title'>
      {{ $pullRequest->title }}

      @php
        $labels = is_string($pullRequest->labels) ? json_decode($pullRequest->labels, true) : $pullRequest->labels;
      @endphp
      @foreach ($labels as $label)
        @php
          $hex = ltrim($label['color'], '#');
          $r = hexdec(substr($hex, 0, 2));
          $g = hexdec(substr($hex, 2, 2));
          $b = hexdec(substr($hex, 4, 2));
          $luminance = (0.299*$r + 0.587*$g + 0.114*$b) / 255;
          $textColor = $luminance > 0.5 ? '#000000' : '#FFFFFF';
        @endphp

        <span class="label" style="background-color: #{{ $label['color'] }}; color: {{ $textColor }};">
          {{ $label['name'] }}
        </span>
      @endforeach
    </h3>
    <span class='opened-by'>Opened by {{ $pullRequest->openedBy->name }} <img src="{{ $pullRequest->openedBy->avatar_url }}" alt="{{ $pullRequest->openedBy->name }}">{{ $pullRequest->created_at->diffForHumans() }}</span>
  </div>

  <div class="side-info">
  @foreach ($pullRequest->assignees_data as $assignee)
    <img src="{{ $assignee->avatar_url }}" alt="{{ $assignee->name }}">
  @endforeach
</div>
</div>
</a>
