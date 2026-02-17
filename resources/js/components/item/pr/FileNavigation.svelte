<script>
  let { files = [], selectedFileIndex = $bindable(0), selectedFile = $bindable(null), reviewMenuOpen = $bindable(false), totalAdditions = $bindable(0), totalDeletions = $bindable(0) } = $props();

  let showDetailsModal = $state(false);
</script>

<!-- Mobile backdrop for details modal -->
{#if showDetailsModal}
  <div class="modal-backdrop" onclick={() => showDetailsModal = false}></div>
{/if}

<!-- Mobile details modal -->
{#if showDetailsModal}
  <div class="file-details-modal">
    <div class="modal-header">
      <span>File Details</span>
      <button class="modal-close" type="button" onclick={() => showDetailsModal = false}>✕</button>
    </div>

    <div class="modal-content">
      <div class="detail-item">
        <span class="detail-label">Files Changed</span>
        <span class="detail-value">{files.length}</span>
      </div>

      <div class="detail-item">
        <span class="detail-label">Additions</span>
        <span class="detail-value additions">+{totalAdditions}</span>
      </div>

      <div class="detail-item">
        <span class="detail-label">Deletions</span>
        <span class="detail-value deletions">-{totalDeletions}</span>
      </div>

      <div class="detail-navigation">
        <button onclick={() => selectedFileIndex--} class="nav-button" disabled={selectedFileIndex === 0} type="button">← Previous</button>
        <span class="file-counter">{selectedFileIndex + 1} / {files.length}</span>
        <button onclick={() => selectedFileIndex++} class="nav-button" disabled={selectedFileIndex === files.length - 1} type="button">Next →</button>
      </div>
    </div>
  </div>
{/if}

<div class="pr-navigation">
  <div class="tab-navigation">
    <button onclick={() => selectedFileIndex--} class="tab-button" disabled={selectedFileIndex === 0} type="button">Previous File</button>
    <button onclick={() => selectedFileIndex++} class="tab-button" disabled={selectedFileIndex === files.length - 1} type="button">Next File</button>
  </div>

  <div class="pr-stats-summary">
    <span class="stats-label">{files.length} {files.length === 1 ? 'file' : 'files'} changed</span>
    <span class="stats-additions">+{totalAdditions}</span>
    <span class="stats-deletions">-{totalDeletions}</span>
  </div>

  <!-- Mobile details icon button -->
  <button class="details-icon-button" type="button" onclick={() => showDetailsModal = true} title="View file details">👁️</button>

  <button class="button-primary" onclick={() => reviewMenuOpen = !reviewMenuOpen}>Add review</button>
</div>

<style lang="scss">
  @import '../../../../scss/components/item/navigation';
  @import '../../../../scss/components/item/pr/filetab/navigation';
</style>
