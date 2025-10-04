<x-email-layout>
  <div class='email-header'>
    <img src="{{ $pullRequestReview->user->avatar_url }}" alt="{{ $pullRequestReview->user->name }}'s avatar" class='email-avatar'>
    {{ $pullRequestReview->user->name }} {{ $pullRequestReview->state }} your pull request "{{ $pullRequestReview->pullRequest->title }}".
  </div>

  <div class='email-body'>
    <x-markdown theme="github-dark">{!! $pullRequestReview->body !!}</x-markdown>
  </div>

  @dump($pullRequestReview->relatedComments)

  @vite(["resources/scss/emails/pull_request_reviewed.scss", "resources/scss/shared/markdown.scss"])
</x-email-layout>