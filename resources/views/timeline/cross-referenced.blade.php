<div class="timeline-event timeline-cross-referenced">
  <div class="timeline-icon">
    <svg class="octicon" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true">
      <path fill-rule="evenodd" d="M4 9h1v1H4c-1.5 0-3-1.69-3-3.5S2.55 3 4 3h4c1.45 0 3 1.69 3 3.5 0 1.41-.91 2.72-2 3.25V8.59c.58-.45 1-1.27 1-2.09C10 5.22 8.98 4 8 4H4c-.98 0-2 1.22-2 2.5S3 9 4 9zm9-3h-1v1h1c1 0 2 1.22 2 2.5S13.98 12 13 12H9c-.98 0-2-1.22-2-2.5 0-.83.42-1.64 1-2.09V6.25c-1.09.53-2 1.84-2 3.25C6 11.31 7.55 13 9 13h4c1.45 0 3-1.69 3-3.5S14.5 6 13 6z"></path>
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
      <span class="timeline-action">referenced this issue</span>
      @if(isset($data['referenced_pr']) && $data['referenced_pr']['number'])
        <span class="timeline-action">in <a href="{{ $data['referenced_pr']['html_url'] ?? '#' }}" class="timeline-pr-link" target="_blank">#{{ $data['referenced_pr']['number'] }}</a></span>
      @endif
      <span class="timeline-time" title="{{ $event->created_at_github->format('Y-m-d H:i:s') }}">
        {{ $event->created_at_github->diffForHumans() }}
      </span>
    </div>
  </div>
</div>