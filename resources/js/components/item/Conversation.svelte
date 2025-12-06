<script>
  import Markdown from '../Markdown.svelte';
  import Comment from '../Comment.svelte';

  let { item, params = {} } = $props();
</script>

<Markdown content={item.body} />

{#each item.comments as comment}
  <Comment {comment} {params} />
{/each}
