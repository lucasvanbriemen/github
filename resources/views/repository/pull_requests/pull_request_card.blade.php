<a href="{{ route('repository.prs.show', [$organization->name, $repository->name, $pullRequest->number]) }}" class="pullrequest-card card {{ $pullRequest->state }}">
  {!! svg('pull_request') !!}
  <div class='main-info'>
    <h3 class='issue-title'>
      {{ $pullRequest->title }}

      @php
        $labels = is_string($pullRequest->labels) ? json_decode($pullRequest->labels, true) : $pullRequest->labels;
      @endphp
      @foreach ($labels as $label)
        <span class="label" style="background-color: {{ labelColor($label['color'])['background'] }}; color: {{ labelColor($label['color'])['text'] }};border: 1px solid {{ labelColor($label['color'])['border'] }};">
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
