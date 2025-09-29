@if (empty($files))
  <div class="diff-empty">No changes</div>
@else
  @foreach ($files as $file)
    @php
      $fileName = $file['to'] === '/dev/null' ? $file['from'] : $file['to'];
      $fileStatus = $file['from'] === '/dev/null' ? 'added'
                    : ($file['to'] === '/dev/null' ? 'deleted'
                    : ($file['from'] !== $file['to'] ? 'renamed' : 'modified'));
    @endphp

    <div class="diff-file" data-file="{{ $fileName }}">
      {{-- File Header --}}
      <div class="diff-file-header">
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

            {{-- Render lines --}}
            @foreach ($lines as $linePair)
              <tr>
                {{-- Left side --}}
                @if (!$linePair['left'] || $linePair['left']['type'] === 'empty')
                  <td class="diff-line-number diff-line-number-empty"></td><td class="diff-line-content diff-line-empty"></td>
                @else
                  @php
                    $line = $linePair['left'];
                    $typeClass = $line['type'] === 'normal' ? '' : 'diff-line-' . $line['type'];
                    $prefix = $line['type'] === 'add' ? '+' : ($line['type'] === 'del' ? '-' : ' ');
                  @endphp
                  <td class="diff-line-number {{ $typeClass }}" data-line-number="{{ $line['lineNumber'] }}">{{ $line['lineNumber'] }}</td><td class="diff-line-content {{ $typeClass }}"><span class="diff-line-prefix">{{ $prefix }}</span><span class="diff-line-code">{{ $line['content'] }}</span></td>
                @endif
                {{-- Right side --}}
                @if (!$linePair['right'] || $linePair['right']['type'] === 'empty')
                  <td class="diff-line-number diff-line-number-empty"></td><td class="diff-line-content diff-line-empty"></td>
                @else
                  @php
                    $line = $linePair['right'];
                    $typeClass = $line['type'] === 'normal' ? '' : 'diff-line-' . $line['type'];
                    $prefix = $line['type'] === 'add' ? '+' : ($line['type'] === 'del' ? '-' : ' ');
                  @endphp
                  <td class="diff-line-number {{ $typeClass }}" data-line-number="{{ $line['lineNumber'] }}">{{ $line['lineNumber'] }}</td><td class="diff-line-content {{ $typeClass }}"><span class="diff-line-prefix">{{ $prefix }}</span><span class="diff-line-code">{{ $line['content'] }}</span></td>
                @endif
              </tr>
            @endforeach
          @endforeach
        </table>
      </div>
    </div>
  @endforeach
@endif