<a href="{{ route('repository.issues.show', [$organization->name, $repository->name, $issue->number]) }}" class="issue-card card {{ $issue->state }}">
  {!! svg('issue') !!}
  <div class='main-info'>
    <h3 class='issue-title'>
      {{ $issue->title }}
      
      @php
        $labels = is_string($issue->labels) ? json_decode($issue->labels, true) : $issue->labels;
      @endphp
      @foreach ($labels as $label)
        <span class="label" style="background-color: {{ labelColor($label['color'])['background'] }}; color: {{ labelColor($label['color'])['text'] }};border: 1px solid {{ labelColor($label['color'])['border'] }};">
          {{ $label['name'] }}
        </span>
      @endforeach
    </h3>
    <span class='opened-by'>Opened by {{ $issue->openedBy->name }} <img src="{{ $issue->openedBy->avatar_url }}" alt="{{ $issue->openedBy->name }}">{{ $issue->created_at->diffForHumans() }}</span>
  </div>

  <div class="side-info">
  @foreach ($issue->assignees_data as $assignee)
    <img src="{{ $assignee->avatar_url }}" alt="{{ $assignee->name }}">
  @endforeach
</div>
</a>
