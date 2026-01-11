<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Models\WorkflowJob;

class WorkflowJobController extends Controller
{
    public function getLogs($organizationName, $repositoryName, $jobId)
    {
        $job = WorkflowJob::find($jobId);
        $rawLogs = ApiHelper::githubApi('/repos/' . $organizationName . '/' . $repositoryName . '/actions/jobs/' . $jobId . '/logs', 'GET', null, true);
        $workflowFile = $this->getWorkflowFile($organizationName, $repositoryName);
        $jobSteps = json_decode($job->steps, true);

        // Extract run command for each job step from the workflow definition
        $runCommands = [];
        foreach ($jobSteps as $step) {
            $runCommand = $this->getRunCommandFromWorkflow($workflowFile, $step['name']);
            if ($runCommand) {
                $runCommands[$step['name']] = $runCommand;
            }
        }

        // Process logs: match them to steps and organize into groups
        $processedSteps = $this->matchLogsToJobSteps($rawLogs, $jobSteps, $runCommands);

        return response()->json($processedSteps);
    }

    public function getWorkflowFile($organizationName, $repositoryName)
    {
        $route = '/repos/' . $organizationName . '/' . $repositoryName . '/contents/.github/workflows/ruby.yml';
        $body = ApiHelper::githubApi($route, 'GET', null, true, ['Accept' => 'application/vnd.github.v3.raw']);

        return $body;
    }

    private function stripTimestamps($text)
    {
        // Pattern matches: 2024-01-10T12:34:56.1234567Z
        return preg_replace('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z\s*/', '', $text);
    }

    private function getRunCommandFromWorkflow($yamlText, $stepName)
    {
        $lines = explode("\n", $yamlText);

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // Find the line with "name:" that matches our step name
            if (strpos($line, 'name:') != true || strpos($line, $stepName) != true) {
                continue;
            }

            // Search for the "run:" command in the next 20 lines
            for ($j = $i; $j < min($i + 20, count($lines)); $j++) {
                $nextLine = $lines[$j];

                if (!preg_match('/^\s+run:/', $nextLine)) {
                    continue;
                }

                // Extract the command, removing "run:" and quotes
                $runCommand = preg_replace('/^\s+run:\s*[\'"]?/', '', $nextLine);
                $runCommand = preg_replace('/[\'"]?\s*$/', '', $runCommand);

                // Multi-line commands in YAML are indented on following lines
                $k = $j + 1;
                while ($k < count($lines)) {
                    $cmdLine = $lines[$k];
                    // Continue adding lines that are indented but not list items or new keys
                    if (preg_match('/^\s+/', $cmdLine) && !preg_match('/^\s+-\s+/', $cmdLine) && !preg_match('/^\s+\w+:/', $cmdLine)) {
                        $runCommand .= ' ' . trim($cmdLine);
                        $k++;
                    } else {
                        break;
                    }
                }

                return $runCommand;
            }
        }

        return null;
    }

    private function parseLogsIntoMap($logText)
    {
        $map = [];
        $lines = explode("\n", $logText);
        $currentStepName = null;
        $currentLogs = [];

        foreach ($lines as $line) {
            // Check for start of a group
            if (preg_match('/##\[group\](.*)/', $line, $matches)) {
                // Save the previous group if one was being collected
                if ($currentStepName) {
                    $map[$currentStepName] = trim(implode("\n", array_map([$this, 'stripTimestamps'], $currentLogs)));
                }
                // Start collecting a new group
                $currentStepName = trim($matches[1]);
                $currentLogs = [];
                continue;
            }

            // Check for end of a group
            if (strpos($line, '##[endgroup]') !== false) {
                // Save the current group
                if ($currentStepName) {
                    $map[$currentStepName] = trim(implode("\n", array_map([$this, 'stripTimestamps'], $currentLogs)));
                }
                $currentStepName = null;
                $currentLogs = [];
                continue;
            }

            // Collect lines that are part of the current group
            if ($currentStepName) {
                $currentLogs[] = $line;
            }
        }

        // Save any remaining group (in case file doesn't end with ##[endgroup])
        if ($currentStepName) {
            $map[$currentStepName] = trim(implode("\n", array_map([$this, 'stripTimestamps'], $currentLogs)));
        }

        return $map;
    }

    private function matchLogsToJobSteps($rawLogText, $jobSteps, $runCommands)
    {
        $lines = explode("\n", $rawLogText);
        $cleanLines = array_map([$this, 'stripTimestamps'], $lines);

        // Process each job step
        $result = [];
        foreach ($jobSteps as $stepIndex => $step) {
            $runCommand = $runCommands[$step['name']] ?? null;
            $stepLogs = [];
            $stepStartIndex = -1;

            if ($runCommand) {
                // Find where this step's command starts in the logs
                for ($i = 0; $i < count($cleanLines); $i++) {
                    $line = $cleanLines[$i];

                    // The run command appears in the logs when the step starts
                    if (strpos($line, $runCommand) !== false) {
                        $stepStartIndex = $i;
                        break;
                    }
                }

                // Collect all logs for this step
                if ($stepStartIndex !== -1) {
                    // Collect logs from where the command appears until we hit the next step
                    for ($i = $stepStartIndex; $i < count($cleanLines); $i++) {
                        // Check if we've reached the next step's run command
                        if ($stepIndex + 1 < count($jobSteps)) {
                            $nextRunCommand = $runCommands[$jobSteps[$stepIndex + 1]['name']] ?? null;
                            if ($nextRunCommand && strpos($cleanLines[$i], $nextRunCommand) !== false) {
                                // Stop here - this is where the next step starts
                                break;
                            }
                        }

                        $stepLogs[] = $cleanLines[$i];
                    }
                }
            }

            // Combine all step logs into one string
            $stepLogText = trim(implode("\n", $stepLogs));

            // Extract all ##[group]...##[endgroup] sections from the logs
            $logGroups = $this->parseLogsIntoMap($stepLogText);

            $logLines = explode("\n", $stepLogText);

            // Capture any output that appears after groups (ungrouped output)
            $ungroupedLogs = [];
            $inUngroupedSection = false;
            for ($i = 0; $i < count($logLines); $i++) {
                if (strpos($logLines[$i], '##[endgroup]') !== false) {
                    // We've exited a group, start collecting ungrouped output
                    $inUngroupedSection = true;
                    continue;
                }

                if ($inUngroupedSection && strpos($logLines[$i], '##[group]') !== false) {
                    // We've entered a new group, stop collecting ungrouped output
                    $inUngroupedSection = false;
                }

                // Collect non-empty lines in ungrouped sections
                if ($inUngroupedSection && trim($logLines[$i])) {
                    $ungroupedLogs[] = $logLines[$i];
                }
            }

            // Build the log groups array for the frontend
            $logGroupsArray = [];
            foreach ($logGroups as $name => $logs) {
                // Only include non-empty groups
                if ($logs && trim($logs) !== '') {
                    $logGroupsArray[] = [
                        'name' => $name,
                        'logs' => $logs,
                        'isExpanded' => false
                    ];
                }
            }

            // Add ungrouped logs as a special "Output" group if they exist
            if (!empty($ungroupedLogs)) {
                $logGroupsArray[] = [
                    'name' => 'Output',
                    'logs' => trim(implode("\n", $ungroupedLogs)),
                    'isExpanded' => $step['conclusion'] === 'failure'
                ];
            }

            // Create the final step object with all metadata and organized logs
            $result[] = [
                'name' => $step['name'],
                'status' => $step['status'],
                'conclusion' => $step['conclusion'],
                'logGroups' => $logGroupsArray,     // Array of collapsible log groups
                'logs' => $stepLogText,               // Full raw logs for this step
                'isExpanded' => $step['conclusion'] === 'failure'
            ];
        }

        return $result;
    }
}
