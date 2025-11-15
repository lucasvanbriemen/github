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

            $file_to_parse['changes'] = $this->formatPatchString($file->patch);

            $parsed_files[] = $file_to_parse;
        }

        $this->files = $parsed_files;

    }

    private function formatPatchString(string $patch)
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

        if ($patch === '') {
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

        return $hunks;
    }
}
