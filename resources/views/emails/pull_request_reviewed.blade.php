<x-email-layout>
  <div class='email-header'>
    <img src="{{ $pullRequestReview->user->avatar_url }}" alt="{{ $pullRequestReview->user->name }}'s avatar" class='email-avatar'>
    {{ $pullRequestReview->user->name }} {{ $pullRequestReview->state }} your pull request "{{ $pullRequestReview->pullRequest->title }}".
  </div>

  <div class='email-body'>
    <x-markdown theme="github-dark">{!! $pullRequestReview->body !!}</x-markdown>
  </div>

  @foreach ($pullRequestReview->relatedComments as $comment)
    <hr>

    <div class='email-header'>
      <img src="{{ $comment->author->avatar_url }}" alt="{{ $comment->author->name }}'s avatar" class='email-avatar'>
      {{ $comment->author->name }} commented:
    </div>
    <div class='email-body'>
      <x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown>
    </div>
  @endforeach

  @vite(["resources/scss/emails/pull_request_reviewed.scss", "resources/scss/shared/markdown.scss"])
</x-email-layout>