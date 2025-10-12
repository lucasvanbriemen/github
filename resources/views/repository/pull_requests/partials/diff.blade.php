@if (empty($files))
  <div class="diff-empty">No changes</div>
@else
  @foreach ($files as $file)
    @php
      $fileName = $file['to'] === '/dev/null' ? $file['from'] : $file['to'];
      $fileStatus = $file['from'] === '/dev/null' ? 'added'
                    : ($file['to'] === '/dev/null' ? 'deleted'
                    : ($file['from'] !== $file['to'] ? 'renamed' : 'modified'));
      $isViewed = isset($viewedFiles) && in_array($fileName, $viewedFiles);
    @endphp

    <div class="diff-file{{ $isViewed ? ' viewed' : '' }}" data-file="{{ $fileName }}">
      {{-- File Header --}}
      <div class="diff-file-header" data-file="{{ $fileName }}">
        <div class="diff-file-header-left">
          <span class="diff-file-status diff-file-status-{{ $fileStatus }}">{{ $fileStatus }}</span>
          <span class="diff-file-name">{{ $fileName }}</span>
        </div>
        <div class="diff-file-stats">
          <span class="diff-stats-additions">+{{ $file['additions'] ?? 0 }}</span>
          <span class="diff-stats-deletions">-{{ $file['deletions'] ?? 0 }}</span>
        </div>
      </div>

      {{-- Diff Content --}}
      <div class="diff-table-container">
        <table class="diff-table diff-table-side-by-side">
          @foreach ($file['chunks'] as $chunk)
            {{-- Process chunk lines for side-by-side --}}
            @php
              $lines = [];
              $leftIndex = $chunk['oldStart'];
              $rightIndex = $chunk['newStart'];

              foreach ($chunk['changes'] as $change) {
                if ($change['type'] === 'normal') {
                  $lines[] = [
                    'left' => ['lineNumber' => $leftIndex++, 'content' => $change['content'], 'type' => 'normal'],
                    'right' => ['lineNumber' => $rightIndex++, 'content' => $change['content'], 'type' => 'normal']
                  ];
                } elseif ($change['type'] === 'del') {
                  $lines[] = [
                    'left' => ['lineNumber' => $leftIndex++, 'content' => $change['content'], 'type' => 'del'],
                    'right' => ['lineNumber' => null, 'content' => '', 'type' => 'empty']
                  ];
                } elseif ($change['type'] === 'add') {
                  $prevLine = !empty($lines) ? $lines[count($lines) - 1] : null;
                  if ($prevLine && $prevLine['right']['type'] === 'empty' && $prevLine['left']['type'] === 'del') {
                    $lines[count($lines) - 1]['right'] = ['lineNumber' => $rightIndex++, 'content' => $change['content'], 'type' => 'add'];
                  } else {
                    $lines[] = [
                      'left' => ['lineNumber' => null, 'content' => '', 'type' => 'empty'],
                      'right' => ['lineNumber' => $rightIndex++, 'content' => $change['content'], 'type' => 'add']
                    ];
                  }
                }
              }
            @endphp

            {{-- Get comments for this file --}}
            @php
              $allComments = $pullRequest->pullRequestComments()
                ->where('path', $fileName)
                ->get();

              // Group by side and line number
              // Both LEFT and RIGHT comments use line_end for the line number
              $leftComments = $allComments->where('side', 'LEFT')->groupBy('line_end');
              $rightComments = $allComments->where('side', 'RIGHT')->groupBy('line_end');

            @endphp

            {{-- Render lines --}}
            @foreach ($lines as $lineIndex => $linePair)
              <tr class="diff-line-row" data-file-path="{{ $fileName }}">
                {{-- Left side --}}
                @if (!$linePair['left'] || $linePair['left']['type'] === 'empty')
                  <td class="diff-line-number diff-line-number-empty"></td><td class="diff-line-content diff-line-empty"></td>
                @else
                  @php
                    $line = $linePair['left'];
                    $typeClass = $line['type'] === 'normal' ? '' : 'diff-line-' . $line['type'];
                    $prefix = $line['type'] === 'add' ? '+' : ($line['type'] === 'del' ? '-' : ' ');
                    $side = $line['type'] === 'del' ? 'LEFT' : 'RIGHT';
                  @endphp
                  <td class="diff-line-number {{ $typeClass }}" data-line-number="{{ $line['lineNumber'] }}">
                    {{ $line['lineNumber'] }}
                    @if ($line['type'] === 'add' || $line['type'] === 'del')
                      <button class="add-inline-comment-btn button-primary" title="Add inline comment">{!! svg('plus') !!}</button>
                    @endif
                  </td>
                  <td class="diff-line-content {{ $typeClass }}"><span class="diff-line-prefix">{{ $prefix }}</span><span class="diff-line-code">{{ $line['content'] }}</span></td>
                @endif
                {{-- Right side --}}
                @if (!$linePair['right'] || $linePair['right']['type'] === 'empty')
                  <td class="diff-line-number diff-line-number-empty"></td><td class="diff-line-content diff-line-empty"></td>
                @else
                  @php
                    $line = $linePair['right'];
                    $typeClass = $line['type'] === 'normal' ? '' : 'diff-line-' . $line['type'];
                    $prefix = $line['type'] === 'add' ? '+' : ($line['type'] === 'del' ? '-' : ' ');
                    $side = "RIGHT";
                  @endphp
                  <td class="diff-line-number {{ $typeClass }}" data-line-number="{{ $line['lineNumber'] }}">
                    {{ $line['lineNumber'] }}
                    @if ($line['type'] === 'add' || $line['type'] === 'del')
                      <button class="add-inline-comment-btn button-primary" title="Add inline comment">{!! svg('plus') !!}</button>
                    @endif
                  </td>
                  <td class="diff-line-content {{ $typeClass }}"><span class="diff-line-prefix">{{ $prefix }}</span><span class="diff-line-code">{{ $line['content'] }}</span></td>
                @endif
              </tr>

              {{-- Render comments for this line (check both left and right sides) --}}
              @php
                $leftLineNum = $linePair['left']['lineNumber'] ?? null;
                $rightLineNum = $linePair['right']['lineNumber'] ?? null;

                $leftCommentsToShow = collect();
                $rightCommentsToShow = collect();

                if ($leftLineNum && isset($leftComments[$leftLineNum])) {
                  $leftCommentsToShow = $leftComments[$leftLineNum];
                }
                if ($rightLineNum && isset($rightComments[$rightLineNum])) {
                  $rightCommentsToShow = $rightComments[$rightLineNum];
                }
              @endphp

              @if ($leftCommentsToShow->isNotEmpty() || $rightCommentsToShow->isNotEmpty())
                <tr class="diff-comment-row">
                  {{-- Left side comments --}}
                  <td colspan="2" class="diff-comment-container">
                    @foreach ($leftCommentsToShow as $comment)
                      @include('repository.pull_requests.partials.pr-comment', ['comment' => $comment, 'replies' => $comment->replies, 'hideDiffHunks' => true])
                    @endforeach
                  </td>
                  {{-- Right side comments --}}
                  <td colspan="2" class="diff-comment-container">
                    @foreach ($rightCommentsToShow as $comment)
                      @include('repository.pull_requests.partials.pr-comment', ['comment' => $comment, 'replies' => $comment->replies , 'hideDiffHunks' => true])
                    @endforeach
                  </td>
                </tr>
              @endif
            @endforeach
          @endforeach
        </table>
      </div>
    </div>
  @endforeach

  <table class="comment-holder-table" hidden>
    {{-- Hidden by default, moved into place when adding a comment --}}
    <tr class="add-inline-comment-wrapper">
      <td colspan="2" class="inline-comment-form">
        <x-markdown-editor
          name="inline-comment"
          id="inline-comment"
          placeholder="Add a comment..."
        />

        <div class="inline-comment-form-actions">
          <button class="button-primary inline-comment-submit">Add comment</button>
          <button class="button-secondary inline-comment-cancel">Cancel</button>
        </div>
      </td>
    </tr>
  </table>
@endif