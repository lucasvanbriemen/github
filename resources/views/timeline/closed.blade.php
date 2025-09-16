<div class="timeline-event timeline-closed">
  <div class="timeline-icon">
    <svg class="octicon" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true">
      <path d="M11.28 6.78a.75.75 0 00-1.06-1.06L7.25 8.69 5.78 7.22a.75.75 0 00-1.06 1.06l2 2a.75.75 0 001.06 0l3.5-3.5z"></path>
      <path fill-rule="evenodd" d="M16 8A8 8 0 110 8a8 8 0 0116 0zm-1.5 0a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"></path>
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
      <span class="timeline-action">
        closed this
        @if($issue && $issue->state_reason === 'completed')
          as completed
        @elseif(isset($data['state_reason']) && $data['state_reason'] === 'completed')
          as completed
        @else
          issue
        @endif
        @if(isset($data['closed_by_pr']) && $data['closed_by_pr']['number'])
          in <a href="{{ $data['closed_by_pr']['html_url'] ?? '#' }}" class="timeline-pr-link" target="_blank">#{{ $data['closed_by_pr']['number'] }}</a>
        @elseif($issue && $issue->closed_by && isset($issue->closed_by['login']) && $issue->closed_by['type'] === 'Bot')
          {{-- Look for PR information in the commit data --}}
          @if(isset($data['commit_info']) && isset($data['commit_info']['commit_url']))
            @php
              // Try to extract PR number from commit URL patterns
              $commitUrl = $data['commit_info']['commit_url'];
              $prNumber = null;
              if (preg_match('/\/pull\/(\d+)/', $commitUrl, $matches)) {
                  $prNumber = $matches[1];
              }
            @endphp
            @if($prNumber)
              in <a href="{{ str_replace('/commit/', '/pull/', dirname($commitUrl)) }}" class="timeline-pr-link" target="_blank">#{{ $prNumber }}</a>
            @endif
          @endif
        @endif
      </span>
      <span class="timeline-time" title="{{ $event->created_at_github->format('Y-m-d H:i:s') }}">
        {{ $event->created_at_github->diffForHumans() }}
      </span>
    </div>
  </div>
</div>
