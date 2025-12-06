<script>
  import { onMount, untrack } from 'svelte';
  import Markdown from '../Markdown.svelte';
  import Comment from '../Comment.svelte';

  let { item, params = {} } = $props();
  let body = $state(item.body);

  function save_body(e) {
    body = e.value;
    console.log('saving body', body);
  }

</script>

<Markdown content={body} change={save_body} />

{#each item.comments as comment}
  <Comment {comment} {params} />
{/each}
