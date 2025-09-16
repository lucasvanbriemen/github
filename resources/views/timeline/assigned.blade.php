<div class="timeline-event">
  {{ $actor->login }} assigned 
  @foreach ($data as $assignee)
    {{ $assignee['login'] }}
    @if (!$loop->last)
      ,
    @endif
  @endforeach

   {{ $event->created_at_github->diffForHumans() }}
</div>
