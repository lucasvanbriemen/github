<script>
  import { onMount } from 'svelte';
  import { organization, repository } from './stores';
  import StepGroup from './StepGroup.svelte';

  let { job } = $props();

  let steps = $state([]);
  let isLoading = $state(true);
  let error = $state(null);
  let logsMap = $state({});

  function calculateDuration(startedAt, completedAt) {
    if (!startedAt || !completedAt) return null;

    const start = new Date(startedAt);
    const end = new Date(completedAt);
    const durationMs = end - start;
    const seconds = Math.floor(durationMs / 1000);

    if (seconds < 60) {
      return `${seconds}s`;
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    if (remainingSeconds === 0) {
      return `${minutes}m`;
    }

    return `${minutes}m ${remainingSeconds}s`;
  }

  function fetchWorkflowFile() {
    // Call backend endpoint to fetch the workflow file
    return api.get(route('organizations.repositories.workflow-file', {
      $organization,
      $repository
    }));
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

    // For each job step, find its logs by searching for the run command
    return jobSteps.map((step, stepIndex) => {
      const runCommand = runCommands[step.name];
      let stepLogs = [];

      if (runCommand) {
        let foundStep = false;
        let foundIndex = -1;

        for (let i = 0; i < cleanLines.length; i++) {
          const line = cleanLines[i];

          // Look for the run command text
          if (!foundStep && line.includes(runCommand)) {
            foundStep = true;
            foundIndex = i;
            // Start collecting from this line
            stepLogs.push(line);
            continue;
          }

          // If we found the step, collect lines until next significant marker
          if (foundStep) {
            stepLogs.push(line);

            // Stop if we encounter another step's run command (look ahead)
            if (stepIndex + 1 < jobSteps.length) {
              const nextRunCommand = runCommands[jobSteps[stepIndex + 1].name];
              if (nextRunCommand && line.includes(nextRunCommand)) {
                stepLogs.pop(); // Remove the line with next command
                break;
              }
            }
          }
        }
      }

      // Join logs and parse into groups
      const stepLogText = stepLogs.join('\n').trim();
      const logGroups = parseLogsIntoMap(stepLogText);

      // Calculate duration from timestamps
      const duration = calculateDuration(step.started_at, step.completed_at);

      return {
        name: step.name,
        number: step.number,
        status: step.status,
        conclusion: step.conclusion,
        started_at: step.started_at,
        completed_at: step.completed_at,
        duration: duration,
        logGroups: Object.entries(logGroups)
          .filter(([name, logs]) => logs && logs.trim().length > 0)
          .map(([name, logs]) => ({
            name: name,
            logs: logs,
            isExpanded: false
          })),
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
    try {
      isLoading = true;
      error = null;

      // Parse job steps from API data
      const jobSteps = typeof job.steps === 'string' ? JSON.parse(job.steps) : job.steps;

      if (!jobSteps || jobSteps.length === 0) {
        error = 'No steps found in job';
        return;
      }

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
      const logText = await api.get(route('organizations.repositories.workflow-job.logs', {
        $organization,
        $repository,
        jobId: job.id
      }));

      if (typeof logText !== 'string') {
        error = 'Failed to fetch job logs';
        return;
      }

      // Match logs to job steps
      steps = matchLogsToJobSteps(logText, jobSteps, runCommands);

    } catch (e) {
      error = e.message || 'Failed to load workflow';
      console.error('Error:', e);
    } finally {
      isLoading = false;
    }
  });
</script>

<div class="job-log-viewer">
  {#if isLoading}
    <div class="loading">
      <p>Loading logs...</p>
    </div>
  {:else if error}
    <div class="error">
      <p>Error: {error}</p>
    </div>
  {:else if steps.length > 0}
    <div class="steps-container">
      {#each steps as step, idx (idx)}
        <StepGroup {step} />
      {/each}
    </div>
  {:else}
    <div class="empty">
      <p>No steps available</p>
    </div>
  {/if}
</div>

<style lang="scss">
  @import '../../scss/components/job-log-viewer';
</style>
