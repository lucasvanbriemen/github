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
        // Todo
    }

    private function parseFiles(): void
    {
        $filesObject = $this->diff->files;
        
        foreach ($filesObject as $file) {
            $parsedFile = [
                'filename' => $file->filename,
                'status' => $file->status,
                'additions' => $file->additions,
                'deletions' => $file->deletions,
            ];
            $this->files[] = $parsedFile;
        }

        // $lines = explode("\n", $this->diffString);
        // $currentFile = null;
        // $currentChunk = null;

        // foreach ($lines as $line) {
        //     // File header: diff --git a/file b/file
        //     if (preg_match('/^diff --git a\/(.+) b\/(.+)$/', $line, $matches)) {
        //         // Save previous file
        //         if ($currentChunk && $currentFile) {
        //             $currentFile['chunks'][] = $currentChunk;
        //         }
        //         if ($currentFile) {
        //             $this->files[] = $currentFile;
        //         }

        //         $currentFile = [
        //             'from' => $matches[1],
        //             'to' => $matches[2],
        //             'chunks' => [],
        //             'additions' => 0,
        //             'deletions' => 0,
        //         ];
        //         $currentChunk = null;
        //     }
        //     // Skip metadata lines (new file mode, index, etc.) and "No newline at end of file"
        //     elseif (preg_match('/^(new file mode|deleted file mode|index|similarity index|rename|copy|\\\\)/', $line)) {
        //         continue;
        //     }
        //     // Old file: --- a/file or --- /dev/null
        //     elseif (preg_match('/^--- (.+)$/', $line, $matches)) {
        //         if ($currentFile) {
        //             $currentFile['from'] = $matches[1] === '/dev/null' ? '/dev/null' : preg_replace('/^a\//', '', $matches[1]);
        //         }
        //     }
        //     // New file: +++ b/file or +++ /dev/null
        //     elseif (preg_match('/^\+\+\+ (.+)$/', $line, $matches)) {
        //         if ($currentFile) {
        //             $currentFile['to'] = $matches[1] === '/dev/null' ? '/dev/null' : preg_replace('/^b\//', '', $matches[1]);
        //         }
        //     }
        //     // Chunk header: @@ -1,4 +1,5 @@
        //     elseif (preg_match('/^@@ -(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? @@(.*)$/', $line, $matches)) {
        //         if ($currentChunk && $currentFile) {
        //             $currentFile['chunks'][] = $currentChunk;
        //         }
        //         $currentChunk = [
        //             'oldStart' => (int)$matches[1],
        //             'oldLines' => isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : 1,
        //             'newStart' => (int)$matches[3],
        //             'newLines' => isset($matches[4]) && $matches[4] !== '' ? (int)$matches[4] : 1,
        //             'content' => trim($matches[5] ?? ''),
        //             'changes' => [],
        //         ];
        //     }
        //     // Change lines
        //     elseif ($currentChunk !== null) {
        //         // Skip completely empty lines, but process lines that start with space/+/-
        //         if (strlen($line) === 0) {
        //             continue;
        //         }

        //         $firstChar = $line[0];
        //         if ($firstChar === '+') {
        //             $currentChunk['changes'][] = ['type' => 'add', 'content' => substr($line, 1)];
        //             if ($currentFile) {
        //                 $currentFile['additions']++;
        //             }
        //         } elseif ($firstChar === '-') {
        //             $currentChunk['changes'][] = ['type' => 'del', 'content' => substr($line, 1)];
        //             if ($currentFile) {
        //                 $currentFile['deletions']++;
        //             }
        //         } elseif ($firstChar === ' ') {
        //             $currentChunk['changes'][] = ['type' => 'normal', 'content' => substr($line, 1)];
        //         }
        //     }
        // }

        // // Add last chunk and file
        // if ($currentChunk && $currentFile) {
        //     $currentFile['chunks'][] = $currentChunk;
        // }
        // if ($currentFile) {
        //     $this->files[] = $currentFile;
        // }
    }
}