<a href="{{ route('repository.issues.show', [$organization->name, $repository->name, $issue->number]) }}" class="issue-card card {{ $issue->state }}">
  {!! svg('issue') !!}
  <div class='main-info'>
    <h3 class='issue-title'>
      {{ $issue->title }}
      
      @php
        $labels = is_string($issue->labels) ? json_decode($issue->labels, true) : $issue->labels;
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
    <span class='opened-by'>Opened by {{ $issue->opened_by_id }} {{ $issue->openedBy->name }} <img src="{{ $issue->openedBy->avatar_url }}" alt="{{ $issue->openedBy->name }}">{{ $issue->created_at->diffForHumans() }}</span>
  </div>

  <div class="side-info">
  @foreach ($issue->assignees_data as $assignee)
    <img src="{{ $assignee->avatar_url }}" alt="{{ $assignee->name }}">
  @endforeach
</div>
</a>
