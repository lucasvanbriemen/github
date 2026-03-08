<script>
  import { organization, repository } from "../../stores";
  import Modal from "../../Modal.svelte";
  import CopyText from "../../CopyText.svelte";
  import AiReviewModal from "../../AiReviewModal.svelte";

  let { item } = $props();
  let number = item.number;

  let mergeConfirmOpen = $state(false);
  let closeConfirmOpen = $state(false);
  let aiReviewModalOpen = $state(false);

  function close() {
    api.post(route(`organizations.repositories.pr.update`, { $organization, $repository, number }), {
      state: 'closed',
    });
    item.state = 'closed';
  }

  function merge() {
    api.post(route(`organizations.repositories.pr.merge`, { $organization, $repository, number }));
    item.state = 'merged';
  }

  function ready_for_review() {
    api.post(route(`organizations.repositories.pr.update`, { $organization, $repository, number }), {
      draft: false,
    });

    item.state = 'open';
  }

  function canMergeOnWorkflowFailure() {
    if (!item.latest_commit?.workflow || item.latest_commit.workflow.conclusion === 'success') {
      return false;
    }

    const failingJobs = item.latest_commit.workflow.jobs.filter(job => job.conclusion === 'failure');

    // Only one failing job and it contains "untested code" in the name
    if (failingJobs.length === 1 && failingJobs[0].name.toLowerCase().includes('untested code')) {
      return false;
    }

    return true;
  }

  function isMergeable() {
    if (item.conflicts?.length > 0 || item.mergeable === false) {
      return false;
    }

    return true;
  }

  function mergeCommand () {
    return `git checkout ${item.details?.base_branch} && git pull && git checkout ${item.details?.head_branch} && git merge ${item.details?.base_branch}`;
  }
</script>

<div class="merge-panel">
  {#if item.latest_commit?.workflow}
    {#if item.latest_commit.workflow.conclusion != 'success'}
      <div class="workflow {item.latest_commit.workflow.conclusion}">
        <span class="workflow-name">{item.latest_commit.workflow.name}</span>
        {#each item.latest_commit.workflow.jobs as job}
          <a class="job {job.conclusion}" href="https://github.com/{$organization}/{$repository}/actions/runs/{job.workflow_id}/job/{job.id}?stay=1" target="_blank">{job.name}</a>
        {/each}
      </div>
    {:else}
      <div class="workflow success">
        <span class="workflow-name">{item.latest_commit.workflow.name}</span>
        <span class="complete-title">All checks have passed</span>
        <span class="complete-metadata">All {item.latest_commit.workflow.jobs.length} jobs completed successfully</span>
      </div>
    {/if}
  {/if}

  {#if item.conflicts?.length > 0}
    <div class="merge-conflicts">
      <span class="conflicts-title">Merge Conflicts</span>
      <span class="conflicts-message">This pull request has merge conflicts that must be resolved before merging.</span>

      <CopyText text={mergeCommand()} label="git merge {item.details?.base_branch}" />

      {#each item.conflicts as conflict}
        <div class="conflict-file">{conflict}</div>
      {/each}
    </div>
  {/if}

  {#if item.mergeable_state === 'blocked'}
    <div class="merge-status blocked">
      <span class="status-message">This pull request cannot be merged because requested changes are pending review.</span>
    </div>
  {/if}

  {#if item.state === 'open' || item.state === 'draft'}
    <button class="button-primary-outline" onclick={() => aiReviewModalOpen = true}>AI Self-Review</button>
  {/if}

  {#if item.state === 'open'}
    {#if !isMergeable()}
      <button class="button-primary" disabled title="This pull request cannot be merged">Merge Pull Request</button>
    {:else if canMergeOnWorkflowFailure()}
      <button class="button-primary-outline" onclick={() => mergeConfirmOpen = true} title="Merge despite workflow failures">Merge Pull Request</button>
    {:else}
      <button class="button-primary" onclick={() => mergeConfirmOpen = true}>Merge Pull Request</button>
    {/if}
    <button class="button-error-outline" onclick={() => closeConfirmOpen = true}>Close Pull Request</button>
  {/if}

  {#if item.state === 'draft'}
    <button class="button-primary ready-for-review" onclick={ready_for_review}>Ready for Review</button>
  {/if}
</div>

<Modal isOpen={mergeConfirmOpen} onClose={() => mergeConfirmOpen = false} onConfirm={merge} title="Merge Pull Request" confirmText="Merge">
  Are you sure you want to merge this pull request? This action will merge the changes into the base branch.
</Modal>
<Modal isOpen={closeConfirmOpen} onClose={() => closeConfirmOpen = false} onConfirm={close} title="Close Pull Request" confirmText="Close">
  Are you sure you want to close this pull request without merging? The pull request can be reopened later.
</Modal>

<AiReviewModal isOpen={aiReviewModalOpen} onClose={() => aiReviewModalOpen = false} {item} />

<style lang="scss">
  @import '../../../../scss/components/item/pr/merge-panel';
</style>
