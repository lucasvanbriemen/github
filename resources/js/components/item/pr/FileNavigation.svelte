<script>
  let { files = [], selectedFileIndex = $bindable(0), selectedFile = $bindable(null) } = $props();
</script>

<div class="tab-navigation">
  <button onclick={() => selectedFileIndex--} class="tab-button" disabled={selectedFileIndex === 0} type="button">Previous File</button>
  <span class="file-nav-info">File {selectedFileIndex + 1} of {files.length}: {selectedFile?.filename}</span>
  <button onclick={() => selectedFileIndex++} class="tab-button" disabled={selectedFileIndex === files.length - 1} type="button">Next File</button>
</div>

<style lang="scss">
  @import '../../../../scss/components/item/navigation';

</style>
