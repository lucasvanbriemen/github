<div class="item-skeleton">
  <!-- Header Skeleton -->
  <div class="skeleton-header">
    <div class="skeleton-title"></div>
    <div class="skeleton-meta">
      <div class="skeleton-text-small"></div>
      <div class="skeleton-avatar-small"></div>
      <div class="skeleton-text-small"></div>
      <div class="skeleton-badge"></div>
    </div>
  </div>

  <!-- Body Skeleton -->
  <div class="skeleton-body">
    <div class="skeleton-text-line"></div>
    <div class="skeleton-text-line"></div>
    <div class="skeleton-text-line" style="width: 80%;"></div>
    <div class="skeleton-text-line" style="width: 90%;"></div>
    <div class="skeleton-text-line" style="width: 70%;"></div>
  </div>

  <!-- Comments Skeleton -->
  {#each Array(3) as _, i}
    <div class="skeleton-comment">
      <div class="skeleton-comment-header">
        <div class="skeleton-avatar-small"></div>
        <div class="skeleton-text-small"></div>
      </div>
      <div class="skeleton-comment-body">
        <div class="skeleton-text-line" style="width: 95%;"></div>
        <div class="skeleton-text-line" style="width: 85%;"></div>
        <div class="skeleton-text-line" style="width: 75%;"></div>
      </div>
    </div>
  {/each}
</div>

<style lang="scss">
  .item-skeleton {
    .skeleton-header {
      margin-bottom: 24px;
      padding: 20px;
      background: var(--background-color-one);
      border-radius: 1rem;

      .skeleton-title {
        height: 32px;
        width: 70%;
        background: linear-gradient(90deg, var(--background-color-two) 25%, var(--border-color) 50%, var(--background-color-two) 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 4px;
        margin-bottom: 12px;
      }

      .skeleton-meta {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .skeleton-text-small {
        height: 16px;
        width: 120px;
        background: linear-gradient(90deg, var(--background-color-two) 25%, var(--border-color) 50%, var(--background-color-two) 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 4px;
      }

      .skeleton-avatar-small {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: linear-gradient(90deg, var(--background-color-two) 25%, var(--border-color) 50%, var(--background-color-two) 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
      }

      .skeleton-badge {
        height: 20px;
        width: 60px;
        background: linear-gradient(90deg, var(--background-color-two) 25%, var(--border-color) 50%, var(--background-color-two) 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 12px;
      }
    }

    .skeleton-body {
      padding: 20px;
      background: var(--background-color-one);
      border-radius: 1rem;
      margin-bottom: 16px;

      .skeleton-text-line {
        height: 16px;
        width: 100%;
        background: linear-gradient(90deg, var(--background-color-two) 25%, var(--border-color) 50%, var(--background-color-two) 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 4px;
        margin-bottom: 12px;

        &:last-child {
          margin-bottom: 0;
        }
      }
    }

    .skeleton-comment {
      padding: 16px;
      background: var(--background-color-one);
      border-radius: 1rem;
      margin-bottom: 16px;

      .skeleton-comment-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
      }

      .skeleton-comment-body {
        padding-left: 32px;

        .skeleton-text-line {
          height: 14px;
          width: 100%;
          background: linear-gradient(90deg, var(--background-color-two) 25%, var(--border-color) 50%, var(--background-color-two) 75%);
          background-size: 200% 100%;
          animation: shimmer 1.5s infinite;
          border-radius: 4px;
          margin-bottom: 10px;

          &:last-child {
            margin-bottom: 0;
          }
        }
      }
    }

    @keyframes shimmer {
      0% {
        background-position: 200% 0;
      }
      100% {
        background-position: -200% 0;
      }
    }
  }
</style>
