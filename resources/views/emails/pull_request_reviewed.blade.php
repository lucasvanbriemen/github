<x-email-layout>
  <div class='email-header'>
    <img src="{{ $pullRequestReview->user->avatar_url }}" alt="{{ $pullRequestReview->user->name }}'s avatar" class='email-avatar'>
    {{ $pullRequestReview->user->name }} {{ $pullRequestReview->state }} your pull request "{{ $pullRequestReview->pullRequest->title }}".
  </div>

  <div class='email-body'>
    <x-markdown theme="github-dark">{!! $pullRequestReview->body !!}</x-markdown>
  </div>

  @vite("resources/scss/emails/pull_request_reviewed.scss")
</x-email-layout>