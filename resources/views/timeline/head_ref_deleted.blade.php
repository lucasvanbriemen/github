<div class="timeline-event timeline-head-ref-deleted">
  <div class="timeline-icon">
    <svg class="octicon" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true">
      <path fill-rule="evenodd" d="M11.5 7a4.499 4.499 0 11-8.998 0A4.499 4.499 0 0111.5 7zm-.82 4.74a6 6 0 111.06-1.06l3.04 3.04a.75.75 0 11-1.06 1.06l-3.04-3.04z"></path>
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
      <span class="timeline-action">deleted the head reference</span>
      <span class="timeline-time" title="{{ $event->created_at_github->format('Y-m-d H:i:s') }}">
        {{ $event->created_at_github->diffForHumans() }}
      </span>
    </div>
  </div>
</div>