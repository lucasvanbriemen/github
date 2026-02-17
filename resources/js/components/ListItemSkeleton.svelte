<div class="list-item-skeleton">
  <div class="skeleton-icon"></div>

  <div class="skeleton-content">
    <div class="skeleton-title"></div>
    <div class="skeleton-meta">
      <div class="skeleton-text"></div>
    </div>
  </div>

  <div class="skeleton-assignees">
    <div class="skeleton-avatar"></div>
    <div class="skeleton-avatar"></div>
  </div>
</div>

<style lang="scss">
  .list-item-skeleton {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--background-color-one);
    border-radius: 1rem;

    .skeleton-icon {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: linear-gradient(90deg, var(--background-color-two) 25%, var(--border-color) 50%, var(--background-color-two) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
      flex-shrink: 0;
    }

    .skeleton-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-width: 0;
    }

    .skeleton-title {
      height: 20px;
      width: 60%;
      background: linear-gradient(90deg, var(--background-color-two) 25%, var(--border-color) 50%, var(--background-color-two) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
      border-radius: 4px;
    }

    .skeleton-meta {
      display: flex;
      gap: 8px;
    }

    .skeleton-text {
      height: 14px;
      width: 200px;
      background: linear-gradient(90deg, var(--background-color-two) 25%, var(--border-color) 50%, var(--background-color-two) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
      border-radius: 4px;
      flex-shrink: 0;

      @media screen and (max-width: 768px) {
        width: 100%;
        max-width: 150px;
      }
    }

    .skeleton-assignees {
      display: flex;
      gap: 8px;
      flex-shrink: 0;
    }

    .skeleton-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: linear-gradient(90deg, var(--background-color-two) 25%, var(--border-color) 50%, var(--background-color-two) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
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
