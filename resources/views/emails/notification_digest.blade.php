@foreach ($groups as $group)
@if ($group['item'])
{{ $group['item']->isPullRequest() ? 'PR' : 'Issue' }} #{{ $group['item']->number }}: {{ $group['item']->title }}
{{ $baseUrl }}/#/{{ $group['item']->repository->organization->name }}/{{ $group['item']->repository->name }}/{{ $group['item']->type === 'pull_request' ? 'pull' : 'issue' }}/{{ $group['item']->number }}
@endif
@foreach ($group['notifications'] as $notification)
  - {{ $notification->subject() }}
@endforeach
@foreach ($group['linked'] as $linked)

  └ PR #{{ $linked['item']->number }}: {{ $linked['item']->title }}
    {{ $baseUrl }}/#/{{ $linked['item']->repository->organization->name }}/{{ $linked['item']->repository->name }}/pull/{{ $linked['item']->number }}
@foreach ($linked['notifications'] as $notification)
    - {{ $notification->subject() }}
@endforeach
@endforeach

@endforeach
@foreach ($orphaned as $notification)
- {{ $notification->subject() }}
  {{ $baseUrl }}/#/notification/{{ $notification->id }}
@endforeach
