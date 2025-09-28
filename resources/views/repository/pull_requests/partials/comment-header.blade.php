<div class="comment-header">
  <span class="author">
    @if(isset($state))
      <span class="state state-{{ $state }}">{!! svg(strtolower($state)) !!}</span>
    @endif
    <img src="{{ $author?->avatar_url }}" alt="{{ $author?->name }}">
    {{ $author?->name }}
  </span>
  <span class="created-at">{{ $createdAt->diffForHumans() }}</span>
</div>