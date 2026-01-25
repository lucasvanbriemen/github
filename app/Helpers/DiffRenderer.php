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
        $oldLines = $hunk['old']['lines'] ?? [];
        $newLines = $hunk['new']['lines'] ?? [];

        // Build a mapping of which new line to pair with each old deletion
        $pairingMap = $this->buildPairingMap($oldLines, $newLines);

        $rows = [];
        $iOld = 0;
        $iNew = 0;
        $oldNo = $hunk['old']['start'] ?? null;
        $newNo = $hunk['new']['start'] ?? null;
        $usedNewIndices = [];

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

            // Changed line: check if we have a pairing
            if ($l && ($l['type'] ?? '') === 'del' && isset($pairingMap[$iOld])) {
                $pairedNewIndex = $pairingMap[$iOld];

                // Output any standalone additions before this pairing
                while ($iNew < $pairedNewIndex) {
                    if (!isset($usedNewIndices[$iNew])) {
                        $newLine = $newLines[$iNew];
                        if (($newLine['type'] ?? '') === 'add') {
                            $rows[] = [
                                'left' => ['number' => null, 'type' => 'empty', 'content' => ''],
                                'right' => ['number' => $newNo, 'type' => 'add', 'content' => (string)($newLine['content'] ?? '')],
                            ];
                            $usedNewIndices[$iNew] = true;
                        }
                    }
                    $iNew++;
                    if ($newNo !== null) { $newNo++; }
                }

                // Now pair the deletion with the matched addition
                $matchedNewLine = $newLines[$pairedNewIndex];
                $leftContent = (string)($l['content'] ?? '');
                $rightContent = (string)($matchedNewLine['content'] ?? '');
                [$leftSeg, $rightSeg] = $this->computeIntralineSegments($leftContent, $rightContent);
                $whitespaceOnly = $this->isWhitespaceOnlyChange($leftContent, $rightContent);

                // Calculate the correct line number for the matched addition
                $matchedNewNo = $newNo;
                $tempI = $iNew;
                while ($tempI <= $pairedNewIndex) {
                    if ($tempI < $pairedNewIndex && !isset($usedNewIndices[$tempI])) {
                        $matchedNewNo++;
                    }
                    $tempI++;
                }

                $rows[] = [
                    'left' => [
                        'number' => $oldNo,
                        'type' => 'del',
                        'content' => $leftContent,
                        'segments' => $leftSeg,
                        'whitespace_only' => $whitespaceOnly,
                    ],
                    'right' => [
                        'number' => $matchedNewNo,
                        'type' => 'add',
                        'content' => $rightContent,
                        'segments' => $rightSeg,
                        'whitespace_only' => $whitespaceOnly,
                    ],
                ];
                $iOld++; $oldNo++;
                $usedNewIndices[$pairedNewIndex] = true;
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
            if ($r && ($r['type'] ?? '') === 'add' && !isset($usedNewIndices[$iNew])) {
                $rows[] = [
                    'left' => ['number' => null, 'type' => 'empty', 'content' => ''],
                    'right' => ['number' => $newNo, 'type' => 'add', 'content' => (string)($r['content'] ?? '')],
                ];
                $iNew++; $newNo++;
                continue;
            }

            // Skip used indices
            if ($r && isset($usedNewIndices[$iNew])) {
                $iNew++;
                if ($newNo !== null) { $newNo++; }
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

            if ($r && ($r['type'] ?? '') === 'normal' && !$l && !isset($usedNewIndices[$iNew])) {
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
     * Build a mapping of old deletion indices to new addition indices based on whitespace-aware similarity.
     * Returns array where key is old index, value is the best matching new index.
     */
    private function buildPairingMap(array $oldLines, array $newLines): array
    {
        $pairingMap = [];
        $usedNewIndices = [];

        // Find all deletions and additions
        $deletions = [];
        $additions = [];

        foreach ($oldLines as $i => $line) {
            if (($line['type'] ?? '') === 'del') {
                $deletions[$i] = $line;
            }
        }

        foreach ($newLines as $i => $line) {
            if (($line['type'] ?? '') === 'add') {
                $additions[$i] = $line;
            }
        }

        // For each deletion, find the best matching addition
        foreach ($deletions as $oldIdx => $delLine) {
            $delContent = (string)($delLine['content'] ?? '');
            $delNoWs = preg_replace('/\s/u', '', $delContent);

            $bestScore = -1;
            $bestNewIdx = null;

            foreach ($additions as $newIdx => $addLine) {
                // Skip already used additions
                if (isset($usedNewIndices[$newIdx])) {
                    continue;
                }

                $addContent = (string)($addLine['content'] ?? '');
                $addNoWs = preg_replace('/\s/u', '', $addContent);

                // Perfect match ignoring whitespace
                if ($delNoWs === $addNoWs && $delNoWs !== '') {
                    $bestScore = 1.0;
                    $bestNewIdx = $newIdx;
                    break; // Take the first perfect match
                }

                // Partial similarity
                $similarity = $this->calculateSimilarity($delNoWs, $addNoWs);
                if ($similarity > $bestScore && $similarity > 0.7) {
                    $bestScore = $similarity;
                    $bestNewIdx = $newIdx;
                }
            }

            if ($bestNewIdx !== null) {
                $pairingMap[$oldIdx] = $bestNewIdx;
                $usedNewIndices[$bestNewIdx] = true;
            }
        }

        return $pairingMap;
    }

    /**
     * Check if two lines differ only in whitespace.
     * Returns true if the lines are identical when all whitespace is removed.
     */
    private function isWhitespaceOnlyChange(string $old, string $new): bool
    {
        $oldNoWs = preg_replace('/\s/u', '', $old);
        $newNoWs = preg_replace('/\s/u', '', $new);
        return $oldNoWs === $newNoWs && $old !== $new;
    }

    /**
     * Compute intra-line diff segments for a pair of texts.
     * Uses smart rules:
     * - Added side: Only highlight if removing the changes from new code gives old code
     * - Removed side: Only highlight if the line is 90%+ similar to new code
     */
    private function computeIntralineSegments(string $old, string $new): array
    {
        if ($old === $new) {
            $seg = [['text' => $old, 'type' => 'equal']];
            return [$seg, $seg];
        }

        // If only whitespace changed, return no highlights
        $oldTrimmed = trim($old);
        $newTrimmed = trim($new);
        if ($oldTrimmed === $newTrimmed) {
            return [
                [['text' => $old, 'type' => 'equal']],
                [['text' => $new, 'type' => 'equal']]
            ];
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

        // Check if we should show highlights on the new (added) side
        // Only if removing the changed part from new gives us old
        $shouldHighlightNew = false;
        if ($newMid !== '') {
            $reconstructed = $preNew . $sufNew;
            if ($reconstructed === $old) {
                $shouldHighlightNew = true;
            }
        }

        // Check if we should show highlights on the old (removed) side
        // Only if the line is 90%+ similar after removing changes
        $shouldHighlightOld = false;
        if ($oldMid !== '') {
            $reconstructed = $pre . $suf;
            $similarity = $this->calculateSimilarity($reconstructed, $new);
            if ($similarity >= 0.9) {
                $shouldHighlightOld = true;
            }
        }

        $leftSeg = [];
        $rightSeg = [];

        if ($pre !== '') { $leftSeg[] = ['text' => $pre, 'type' => 'equal']; }
        if ($oldMid !== '' && $shouldHighlightOld) { $leftSeg[] = ['text' => $oldMid, 'type' => 'change']; }
        elseif ($oldMid !== '') { $leftSeg[] = ['text' => $oldMid, 'type' => 'equal']; }
        if ($suf !== '') { $leftSeg[] = ['text' => $suf, 'type' => 'equal']; }

        if ($preNew !== '') { $rightSeg[] = ['text' => $preNew, 'type' => 'equal']; }
        if ($newMid !== '' && $shouldHighlightNew) { $rightSeg[] = ['text' => $newMid, 'type' => 'change']; }
        elseif ($newMid !== '') { $rightSeg[] = ['text' => $newMid, 'type' => 'equal']; }
        if ($sufNew !== '') { $rightSeg[] = ['text' => $sufNew, 'type' => 'equal']; }

        // If either middle is empty, segments array may end up only equals — that's fine
        if (!$leftSeg) { $leftSeg[] = ['text' => $old, 'type' => 'equal']; }
        if (!$rightSeg) { $rightSeg[] = ['text' => $new, 'type' => 'equal']; }

        // If highlighted portion is 90%+ of the line, don't highlight
        $leftHighlightLen = $this->getSegmentsLength($leftSeg, 'change');
        $rightHighlightLen = $this->getSegmentsLength($rightSeg, 'change');

        if ($leftHighlightLen / max(1, $oldLen) >= 0.9) {
            $leftSeg = [['text' => $old, 'type' => 'equal']];
        }

        if ($rightHighlightLen / max(1, $newLen) >= 0.9) {
            $rightSeg = [['text' => $new, 'type' => 'equal']];
        }

        return [$leftSeg, $rightSeg];
    }

    /**
     * Get total length of segments with a specific type.
     */
    private function getSegmentsLength(array $segments, string $type): int
    {
        $length = 0;
        foreach ($segments as $seg) {
            if (($seg['type'] ?? '') === $type) {
                $length += strlen($seg['text'] ?? '');
            }
        }
        return $length;
    }

    /**
     * Calculate similarity between two strings using longest common subsequence.
     * Returns a value between 0 and 1.
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        $maxLen = max($len1, $len2);

        if ($maxLen === 0) {
            return 1.0;
        }

        $lcs = $this->getLongestCommonSubsequenceLength($str1, $str2);
        return $lcs / $maxLen;
    }

    /**
     * Calculate length of longest common subsequence.
     */
    private function getLongestCommonSubsequenceLength(string $str1, string $str2): int
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        $dp = array_fill(0, $len1 + 1, array_fill(0, $len2 + 1, 0));

        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                if ($str1[$i - 1] === $str2[$j - 1]) {
                    $dp[$i][$j] = $dp[$i - 1][$j - 1] + 1;
                } else {
                    $dp[$i][$j] = max($dp[$i - 1][$j], $dp[$i][$j - 1]);
                }
            }
        }

        return $dp[$len1][$len2];
    }
}
