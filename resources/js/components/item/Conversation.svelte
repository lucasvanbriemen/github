<script>
  import { onMount } from 'svelte';
  import Markdown from '../Markdown.svelte';
  import Comment from '../Comment.svelte';

  let { item, params = {} } = $props();

  onMount(async () => {
    isPR = item.type === 'pull_request';
  });
</script>

<Markdown content={item.body} />

<!-- Issue Comments -->
{#each item.comments as comment}
  <Comment {comment} {params} />
{/each}
