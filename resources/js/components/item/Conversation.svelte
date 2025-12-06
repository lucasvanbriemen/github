<script>
  import { onMount, untrack } from 'svelte';
  import Markdown from '../Markdown.svelte';
  import Comment from '../Comment.svelte';

  let { item, params = {} } = $props();
  let body = $state(item.body);
  let initialized = $state(false);

  $effect(() => {
    void body;
    
    untrack(() => {
      if (!initialized) {
        initialized = true;
        return;
      }

      console.log('saving');
    });
  });
</script>

<Markdown bind:content={body} />

{#each item.comments as comment}
  <Comment {comment} {params} />
{/each}
