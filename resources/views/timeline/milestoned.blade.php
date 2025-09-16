<div class="timeline-event timeline-milestoned">
  <div class="timeline-icon">
    <svg class="octicon" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true">
      <path fill-rule="evenodd" d="M7.75 2a.75.75 0 01.75.75V8h4.44a.75.75 0 01.53 1.28l-6 6a.75.75 0 01-1.06 0l-6-6A.75.75 0 011.22 8H5.5V2.75A.75.75 0 017.25 2h.5z"></path>
    </svg>
  </div>
  <div class="timeline-content">
    <div class="timeline-header">
      @if($actor)
        <img src="{{ $actor->avatar_url }}" alt="{{ $actor->login }}" class="timeline-avatar">
        <strong>{{ $actor->login }}</strong>
      @else
        <strong>Someone</strong>
      @endif
      <span class="timeline-action">added to milestone</span>
      @if(isset($data['milestone']))
        <span class="timeline-milestone">
          {{ $data['milestone']['title'] }}
        </span>
      @endif
      <span class="timeline-time" title="{{ $event->created_at_github->format('Y-m-d H:i:s') }}">
        {{ $event->created_at_github->diffForHumans() }}
      </span>
    </div>
  </div>
</div>