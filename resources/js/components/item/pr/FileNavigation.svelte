<script>
  let { files = [], selectedFileIndex = bindable(0), selectedFile = bindable(null) } = $props();
</script>

<div class="file-nav-wrapper">
  <button onclick={() => selectedFileIndex--} class="file-nav-button" class:disabled={selectedFileIndex === 0} type="button">Previous File</button>
  <span class="file-nav-info">File {selectedFileIndex + 1} of {files.length}: {selectedFile?.filename}</span>
  <button onclick={() => selectedFileIndex++} class="file-nav-button" class:disabled={selectedFileIndex === files.length - 1} type="button">Next File</button>
</div>

<style lang="scss">
  @import '../../../../scss/components/item/pr/filetab/navigation';
</style>
