<script>
  let { files = [], selectedFileIndex = $bindable(0), selectedFile = $bindable(null), reviewMenuOpen = $bindable(false), totalAdditions = $bindable(0), totalDeletions = $bindable(0) } = $props();
</script>

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

  <button class="button-primary" onclick={() => reviewMenuOpen = !reviewMenuOpen}>Add review</button>
</div>

<style lang="scss">
  @import '../../../../scss/components/item/navigation';
  @import '../../../../scss/components/item/pr/filetab/navigation';
</style>
