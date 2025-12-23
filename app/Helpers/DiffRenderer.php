<?php

namespace App\Helpers;

/**
 * Custom GitHub-like diff renderer
 * Parses and renders git diffs in HTML format
 */
class DiffRenderer
{
    private object $diff;
    private array $files = [];
    private array $commits = [];

    public function __construct(object $diff)
    {
        $this->diff = $diff;
        $this->parseFiles();
        $this->parseCommits();
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getCommits(): array
    {
        return $this->commits;
    }

    private function parseCommits(): void
    {
       
    }

    /**
     * Parse files from GitHub compare API response
     * and transform patches into structured hunks/rows.
     */
    private function parseFiles(): void
    {
        $filesObject = $this->diff->files;
        $this->files = $filesObject;

        $parsed_files = [];

        foreach ($this->files as $file) {
            $file_to_parse = [];

            $file_to_parse['filename'] = $file->filename;
            $file_to_parse['status'] = $file->status;
            $file_to_parse['additions'] = $file->additions;
            $file_to_parse['deletions'] = $file->deletions;

            // Build diff hunks and aligned rows for this file
            $file_to_parse['changes'] = $this->formatPatchString($file->patch ?? null);

            $parsed_files[] = $file_to_parse;
        }

        $this->files = $parsed_files;
    }

    /**
     * Convert a unified diff patch string into structured hunks with
     * - old/new line blocks (raw)
     * - rows: aligned pairs for side-by-side display
     *   including intra-line segments for add/del pairs.
     */
    private function formatPatchString(?string $patch)
    {
        // Return a structure per the comment:
        // [
        //   [
        //     'old' => ['start' => int, 'end' => int, 'lines' => [[type, content], ...]],
        //     'new' => ['start' => int, 'end' => int, 'lines' => [[type, content], ...]],
        //   ],
        //   ...
        // ]

        $hunks = [];

        if ($patch === null || $patch === '') {
            return $hunks;
        }

        $lines = preg_split("/\r?\n/", $patch);
        if ($lines === false) {
            return $hunks;
        }

        $currentHunk = null;
        $oldLine = 0;
        $newLine = 0;

        $flushHunk = function () use (&$hunks, &$currentHunk, $oldLine, $newLine) {
            if ($currentHunk === null) {
                return;
            }
            // Set end positions based on last consumed line numbers
            $currentHunk['old']['end'] = $currentHunk['old']['start'] > 0
                ? max($currentHunk['old']['start'], $oldLine - 1)
                : 0;
            $currentHunk['new']['end'] = $currentHunk['new']['start'] > 0
                ? max($currentHunk['new']['start'], $newLine - 1)
                : 0;
            $hunks[] = $currentHunk;
            $currentHunk = null;
        };

        foreach ($lines as $rawLine) {
            // New hunk header
            if (preg_match('/^@@ -(?P<old_start>\d+)(?:,(?P<old_count>\d+))? \+(?P<new_start>\d+)(?:,(?P<new_count>\d+))? @@/', $rawLine, $m)) {
                // close the previous hunk if any
                $flushHunk();

                $oldStart = (int)($m['old_start'] ?? 0);
                $newStart = (int)($m['new_start'] ?? 0);
                $oldLine = $oldStart;
                $newLine = $newStart;

                $currentHunk = [
                    'old' => [
                        'start' => $oldStart,
                        'end' => $oldStart, // will be updated as we parse
                        'lines' => [],
                    ],
                    'new' => [
                        'start' => $newStart,
                        'end' => $newStart, // will be updated as we parse
                        'lines' => [],
                    ],
                ];
                continue;
            }

            // Outside any hunk; ignore non-hunk lines (file headers, metadata)
            if ($currentHunk === null) {
                continue;
            }

            // Handle special meta lines like "\\ No newline at end of file" — skip
            if (strlen($rawLine) > 0 && $rawLine[0] === '\\') {
                continue;
            }

            // Classify line by first char
            $prefix = $rawLine[0] ?? ' ';
            $content = ($prefix === '+' || $prefix === '-' || $prefix === ' ')
                ? substr($rawLine, 1)
                : $rawLine;

            switch ($prefix) {
                case '+':
                    // Addition: only affects new side
                    $currentHunk['new']['lines'][] = [
                        'type' => 'add',
                        'content' => $content,
                    ];
                    $newLine++;
                    break;

                case '-':
                    // Deletion: only affects old side
                    $currentHunk['old']['lines'][] = [
                        'type' => 'del',
                        'content' => $content,
                    ];
                    $oldLine++;
                    break;

                default:
                    // Context line: present on both sides
                    $currentHunk['old']['lines'][] = [
                        'type' => 'normal',
                        'content' => $content,
                    ];
                    $currentHunk['new']['lines'][] = [
                        'type' => 'normal',
                        'content' => $content,
                    ];
                    $oldLine++;
                    $newLine++;
                    break;
            }
        }

        // Flush the last hunk if present
        $flushHunk();

        // Post-process: combine old/new side lines into aligned rows per hunk
        foreach ($hunks as &$hunk) {
            $hunk['rows'] = $this->buildRowsForHunk($hunk);
        }
        unset($hunk);

        return $hunks;
    }

    /**
     * Given a hunk with old/new lines, generate aligned rows for side-by-side view.
     * Also computes intra-line segments for add/del pairs to enable fine-grained highlighting.
     */
    private function buildRowsForHunk(array $hunk): array
    {
        $rows = [];
        $oldLines = $hunk['old']['lines'] ?? [];
        $newLines = $hunk['new']['lines'] ?? [];
        $iOld = 0;
        $iNew = 0;
        $oldNo = $hunk['old']['start'] ?? null;
        $newNo = $hunk['new']['start'] ?? null;

        while ($iOld < count($oldLines) || $iNew < count($newLines)) {
            $l = $iOld < count($oldLines) ? $oldLines[$iOld] : null;
            $r = $iNew < count($newLines) ? $newLines[$iNew] : null;

            // Context lines align 1:1
            if ($l && $r && ($l['type'] ?? '') === 'normal' && ($r['type'] ?? '') === 'normal') {
                $rows[] = [
                    'left' => ['number' => $oldNo, 'type' => 'normal', 'content' => $l['content'] ?? ''],
                    'right' => ['number' => $newNo, 'type' => 'normal', 'content' => $r['content'] ?? ''],
                ];
                $iOld++; $iNew++; $oldNo++; $newNo++;
                continue;
            }

            // Changed line: pair deletion with addition
            if ($l && ($l['type'] ?? '') === 'del' && $r && ($r['type'] ?? '') === 'add') {
                $leftContent = (string)($l['content'] ?? '');
                $rightContent = (string)($r['content'] ?? '');
                [$leftSeg, $rightSeg] = $this->computeIntralineSegments($leftContent, $rightContent);

                $rows[] = [
                    'left' => [
                        'number' => $oldNo,
                        'type' => 'del',
                        'content' => $leftContent,
                        'segments' => $leftSeg,
                    ],
                    'right' => [
                        'number' => $newNo,
                        'type' => 'add',
                        'content' => $rightContent,
                        'segments' => $rightSeg,
                    ],
                ];
                $iOld++; $iNew++; $oldNo++; $newNo++;
                continue;
            }

            // Standalone deletion
            if ($l && ($l['type'] ?? '') === 'del') {
                $rows[] = [
                    'left' => ['number' => $oldNo, 'type' => 'del', 'content' => (string)($l['content'] ?? '')],
                    'right' => ['number' => null, 'type' => 'empty', 'content' => ''],
                ];
                $iOld++; $oldNo++;
                continue;
            }

            // Standalone addition
            if ($r && ($r['type'] ?? '') === 'add') {
                $rows[] = [
                    'left' => ['number' => null, 'type' => 'empty', 'content' => ''],
                    'right' => ['number' => $newNo, 'type' => 'add', 'content' => (string)($r['content'] ?? '')],
                ];
                $iNew++; $newNo++;
                continue;
            }

            // Fallbacks: mirror remaining context if sides misaligned
            if ($l && ($l['type'] ?? '') === 'normal' && !$r) {
                $rows[] = [
                    'left' => ['number' => $oldNo, 'type' => 'normal', 'content' => (string)($l['content'] ?? '')],
                    'right' => ['number' => $newNo, 'type' => 'normal', 'content' => (string)($l['content'] ?? '')],
                ];
                $iOld++; $oldNo++; if ($newNo !== null) { $newNo++; }
                continue;
            }

            if ($r && ($r['type'] ?? '') === 'normal' && !$l) {
                $rows[] = [
                    'left' => ['number' => $oldNo, 'type' => 'normal', 'content' => (string)($r['content'] ?? '')],
                    'right' => ['number' => $newNo, 'type' => 'normal', 'content' => (string)($r['content'] ?? '')],
                ];
                $iNew++; $newNo++; if ($oldNo !== null) { $oldNo++; }
                continue;
            }

            // Safety: prevent infinite loop on unexpected input
            break;
        }

        return $rows;
    }

    /**
     * Compute intra-line diff segments for a pair of texts.
     * Returns two arrays of segments [ ['text' => string, 'type' => 'equal'|'change'], ...]
     * The middle differing portion is marked as 'change'.
     */
    private function computeIntralineSegments(string $old, string $new): array
    {
        if ($old === $new) {
            $seg = [['text' => $old, 'type' => 'equal']];
            return [$seg, $seg];
        }

        $oldLen = strlen($old);
        $newLen = strlen($new);

        // Longest common prefix
        $prefix = 0;
        $limit = min($oldLen, $newLen);
        while ($prefix < $limit && $old[$prefix] === $new[$prefix]) {
            $prefix++;
        }

        // Longest common suffix, ensuring no overlap with prefix
        $suffix = 0;
        while ($suffix < ($limit - $prefix)
            && $old[$oldLen - 1 - $suffix] === $new[$newLen - 1 - $suffix]) {
            $suffix++;
        }

        $oldMid = substr($old, $prefix, $oldLen - $prefix - $suffix);
        $newMid = substr($new, $prefix, $newLen - $prefix - $suffix);
        $pre = substr($old, 0, $prefix);
        $suf = $suffix > 0 ? substr($old, $oldLen - $suffix) : '';
        $preNew = substr($new, 0, $prefix);
        $sufNew = $suffix > 0 ? substr($new, $newLen - $suffix) : '';

        $leftSeg = [];
        $rightSeg = [];

        if ($pre !== '') { $leftSeg[] = ['text' => $pre, 'type' => 'equal']; }
        if ($oldMid !== '') { $leftSeg[] = ['text' => $oldMid, 'type' => 'change']; }
        if ($suf !== '') { $leftSeg[] = ['text' => $suf, 'type' => 'equal']; }

        if ($preNew !== '') { $rightSeg[] = ['text' => $preNew, 'type' => 'equal']; }
        if ($newMid !== '') { $rightSeg[] = ['text' => $newMid, 'type' => 'change']; }
        if ($sufNew !== '') { $rightSeg[] = ['text' => $sufNew, 'type' => 'equal']; }

        // If either middle is empty, segments array may end up only equals — that's fine
        if (!$leftSeg) { $leftSeg[] = ['text' => $old, 'type' => 'equal']; }
        if (!$rightSeg) { $rightSeg[] = ['text' => $new, 'type' => 'equal']; }

        return [$leftSeg, $rightSeg];
    }
}
