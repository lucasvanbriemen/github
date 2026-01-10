<script>
  import { onMount } from 'svelte';
  import { organization, repository } from './stores';
  import StepGroup from './StepGroup.svelte';

  let { job } = $props();

  let steps = $state([]);
  let isLoading = $state(true);
  let error = $state(null);
  let logsMap = $state({});

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

      // Parse steps from job.steps JSON
      const jobSteps = typeof job.steps === 'string' ? JSON.parse(job.steps) : job.steps;

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

      if (!logText || logText.trim().length === 0) {
        error = 'No logs returned from server';
        return;
      }

      // Parse logs into a map by step name
      logsMap = parseLogsIntoMap(logText);

      // Build steps array from all parsed log groups
      steps = Object.entries(logsMap)
        .filter(([name, logs]) => logs && logs.trim().length > 0)
        .map(([name, logs]) => ({
          name: name,
          status: 'completed',
          conclusion: 'success',
          logs: logs,
          isExpanded: false
        }));

    } catch (e) {
      error = e.message || 'Failed to fetch job logs';
      console.error('Error fetching logs:', e);
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
      {#each steps as step (step.name)}
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
