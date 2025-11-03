<script>
  import { Editor } from 'bytemd';
  import gfm from '@bytemd/plugin-gfm';
  import 'bytemd/dist/index.css';
  import { marked } from 'marked';

  let { value = $bindable(''), placeholder = '' } = $props();
  const plugins = [gfm()];

  function handleChange(e) {
    value = e.detail.value;
  }
</script>

<div class="markdown-editor-container">
  <Editor value={value} {plugins} {placeholder} on:change={handleChange} mode="split" />
</div>

<style lang="scss">
  @import '../../scss/components/markdown-editor.scss';
</style>
