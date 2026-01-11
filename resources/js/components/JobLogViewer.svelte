<script>
  import { onMount } from 'svelte';
  import { organization, repository } from './stores';
  import StepGroup from './StepGroup.svelte';

  let { job } = $props();

  let steps = $state([]);
  let isLoading = $state(true);
  let error = $state(null);

  function fetchWorkflowFile() {
    return api.get(route('organizations.repositories.workflow-file', { $organization, $repository }));
  }

  function getRunCommandFromWorkflow(yamlText, stepName) {
    const lines = yamlText.split('\n');

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];

      // Look for step name match
      if (line.includes(`name:`) && line.includes(stepName)) {
        // Search for the run command in the next lines
        for (let j = i; j < Math.min(i + 20, lines.length); j++) {
          const nextLine = lines[j];
          if (nextLine.match(/^\s+run:/)) {
            let runCommand = nextLine.replace(/^\s+run:\s*['"]?/, '').replace(/['"]?\s*$/, '');

            // Handle multiline run commands
            let k = j + 1;
            while (k < lines.length) {
              const cmdLine = lines[k];
              if (cmdLine.match(/^\s+/) && !cmdLine.match(/^\s+-\s+/) && !cmdLine.match(/^\s+\w+:/)) {
                runCommand += ' ' + cmdLine.trim();
                k++;
              } else {
                break;
              }
            }

            return runCommand;
          }
        }
      }
    }

    return null;
  }

  function matchLogsToJobSteps(rawLogText, jobSteps, runCommands) {
    const lines = rawLogText.split('\n');
    const cleanLines = lines.map(line => stripTimestamps(line));

    // For each job step, find its logs
    return jobSteps.map((step, stepIndex) => {
      const runCommand = runCommands[step.name];
      let stepLogs = [];
      let stepStartIndex = -1;

      if (runCommand) {
        // Look for where this step's command appears in the logs
        for (let i = 0; i < cleanLines.length; i++) {
          const line = cleanLines[i];

          // Match the run command (could be in ##[group] or standalone)
          if (line.includes(runCommand)) {
            stepStartIndex = i;
            break;
          }
        }

        if (stepStartIndex !== -1) {
          // Collect everything from step start onwards
          for (let i = stepStartIndex; i < cleanLines.length; i++) {
            // Check if we've hit the NEXT step's run command
            if (stepIndex + 1 < jobSteps.length) {
              const nextRunCommand = runCommands[jobSteps[stepIndex + 1].name];
              if (nextRunCommand && cleanLines[i].includes(nextRunCommand)) {
                // Stop here - don't include the next step's command
                break;
              }
            }

            stepLogs.push(cleanLines[i]);
          }
        }
      }

      // Join logs
      const stepLogText = stepLogs.join('\n').trim();

      // Parse into groups (will only get content within ##[group]...##[endgroup])
      const logGroups = parseLogsIntoMap(stepLogText);

      // Also track which lines are in groups
      const groupedLines = new Set();
      let lastGroupStart = -1;
      let lastGroupEnd = -1;

      const logLines = stepLogText.split('\n');
      for (let i = 0; i < logLines.length; i++) {
        if (logLines[i].includes('##[group]')) {
          lastGroupStart = i;
        }
        if (logLines[i].includes('##[endgroup]')) {
          lastGroupEnd = i;
          // Mark all lines in this group
          for (let j = lastGroupStart; j <= lastGroupEnd; j++) {
            groupedLines.add(j);
          }
        }
      }

      // Find any ungrouped logs (output after ##[endgroup])
      let ungroupedLogs = [];
      let inUngroupedSection = false;
      for (let i = 0; i < logLines.length; i++) {
        if (logLines[i].includes('##[endgroup]')) {
          inUngroupedSection = true;
          continue;
        }

        if (inUngroupedSection && logLines[i].includes('##[group]')) {
          inUngroupedSection = false;
        }

        if (inUngroupedSection && logLines[i].trim()) {
          ungroupedLogs.push(logLines[i]);
        }
      }

      // Build log groups array
      const logGroupsArray = Object.entries(logGroups)
        .filter(([name, logs]) => logs && logs.trim().length > 0)
        .map(([name, logs]) => ({
          name: name,
          logs: logs,
          isExpanded: false
        }));

      // Add ungrouped logs as a special group if they exist
      if (ungroupedLogs.length > 0) {
        logGroupsArray.push({
          name: 'Output',
          logs: ungroupedLogs.join('\n').trim(),
          isExpanded: false
        });
      }

      return {
        name: step.name,
        number: step.number,
        status: step.status,
        conclusion: step.conclusion,
        started_at: step.started_at,
        completed_at: step.completed_at,
        logGroups: logGroupsArray,
        logs: stepLogText,
        isExpanded: false
      };
    });
  }

  function stripTimestamps(text) {
    // Remove ISO timestamps like 2024-01-10T12:34:56.1234567Z
    return text.replace(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z\s*/g, '');
  }

  function parseLogsIntoMap(logText) {
    const map = {};
    const lines = logText.split('\n');
    let currentStepName = null;
    let currentLogs = [];

    for (const line of lines) {
      const groupMatch = line.match(/##\[group\](.*)/);
      if (groupMatch) {
        // Save previous step if exists
        if (currentStepName) {
          map[currentStepName] = currentLogs
            .map(l => stripTimestamps(l))
            .join('\n')
            .trim();
        }
        // Start new step
        currentStepName = groupMatch[1].trim();
        currentLogs = [];
        continue;
      }

      if (line.includes('##[endgroup]')) {
        // Save current step
        if (currentStepName) {
          map[currentStepName] = currentLogs
            .map(l => stripTimestamps(l))
            .join('\n')
            .trim();
        }
        currentStepName = null;
        currentLogs = [];
        continue;
      }

      // Add line to current step
      if (currentStepName) {
        currentLogs.push(line);
      }
    }

    // Save any remaining step
    if (currentStepName) {
      map[currentStepName] = currentLogs
        .map(l => stripTimestamps(l))
        .join('\n')
        .trim();
    }

    return map;
  }

  onMount(async () => {
    isLoading = true;
    error = null;

    // Parse job steps from API data
    const jobSteps = job.steps;

    // Fetch the workflow YAML file
    const workflowYaml = await fetchWorkflowFile();

    // For each job step, get its run command from the workflow
    const runCommands = {};
    jobSteps.forEach(step => {
      const runCommand = getRunCommandFromWorkflow(workflowYaml, step.name);
      if (runCommand) {
        runCommands[step.name] = runCommand;
      }
    });

    // Fetch full logs
    const logText = await api.get(route('organizations.repositories.workflow-job.logs', { $organization, $repository, jobId: job.id }));

    // Match logs to job steps
    steps = matchLogsToJobSteps(logText, jobSteps, runCommands);

    isLoading = false;
  });
</script>

<div class="job-log-viewer">
  {#if isLoading}
    <div class="loading">
      <p>Loading logs...</p>
    </div>
  {:else}
    <div class="steps-container">
      {#each steps as step, idx (idx)}
        <StepGroup {step} />
      {/each}
    </div>
  {/if}
</div>

<style lang="scss">
  @import '../../scss/components/job-log-viewer';
</style>
