<div class="timeline-event timeline-commented">
  <div class="timeline-icon">
    <svg class="octicon" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true">
      <path fill-rule="evenodd" d="M2.75 2.5a.25.25 0 00-.25.25v7.5c0 .138.112.25.25.25h2a.75.75 0 01.75.75v2.19l2.72-2.72a.75.75 0 01.53-.22h4.5a.25.25 0 00.25-.25v-7.5a.25.25 0 00-.25-.25H2.75zM1 2.75C1 1.784 1.784 1 2.75 1h10.5c.966 0 1.75.784 1.75 1.75v7.5A1.75 1.75 0 0113.25 12H9.06l-2.573 2.573A1.5 1.5 0 014 13.543V12H2.75A1.75 1.75 0 011 10.25v-7.5z"></path>
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
      <span class="timeline-action">commented</span>
      <span class="timeline-time" title="{{ $event->created_at_github->format('Y-m-d H:i:s') }}">
        {{ $event->created_at_github->diffForHumans() }}
      </span>
    </div>
    @if(isset($data['body']) && !empty($data['body']))
      <div class="timeline-comment-body">
        <div class="comment-content">
          <x-markdown theme="github-dark">{!! $data['body'] !!}</x-markdown>
        </div>
      </div>
    @endif
  </div>
</div>