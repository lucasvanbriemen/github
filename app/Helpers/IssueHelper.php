<?php

namespace App\Helpers;

class IssueHelper
{
    public static function labelColor($color)
    {
        $borderColor = "#".$color;
        $textColor = "#".$color;

        // Make the background color slightly transparent
        $backgroundColor = "#".$color . '33'; // Adding '33' for 20% opacity

        return [
            'text' => $textColor,
            'background' => $backgroundColor,
            'border' =>  $borderColor,
        ];
    }

    public static function commentDiffHunk($diff, $startLine, $endLine)
    {
        $lines = explode("\n", $diff);
        $hunkLines = [];
        $currentLine = 0;

        foreach ($lines as $line) {
            if (preg_match('/^@@ -\d+(,\d+)? \+(\d+)(,(\d+))? @@/', $line, $matches)) {
                $currentLine = (int)$matches[2];
                continue;
            }

            if (strpos($line, '+') === 0) {
                if ($currentLine >= $startLine && $currentLine <= $endLine) {
                    $hunkLines[] = $line;
                }
                $currentLine++;
            } elseif (strpos($line, '-') === 0) {
                // Removed line, do not increment currentLine
            }
        }

        return $hunkLines;
    }
}
