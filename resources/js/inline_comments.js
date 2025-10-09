export default {
  init() {
    console.log('Inline comments initialized');
    this.attachCommentButtonListeners();
  },

  attachCommentButtonListeners() {
    const commentButtons = document.querySelectorAll('.add-inline-comment-btn');
    console.log('Found comment buttons:', commentButtons.length);

    commentButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        console.log('Comment button clicked');
        e.preventDefault();
        e.stopPropagation();

        const lineNumberCell = button.closest('.diff-line-number');
        const row = button.closest('tr');
        const lineNumber = lineNumberCell.getAttribute('data-line-number');
        const side = lineNumberCell.getAttribute('data-side');
        const filePath = row.getAttribute('data-file-path');

        console.log('Comment data:', { lineNumber, side, filePath });
        this.showCommentForm(row, lineNumber, side, filePath, lineNumberCell);
      });
    });
  },

  showCommentForm(row, lineNumber, side, filePath, lineNumberCell) {
    // Check if a form already exists for this row
    const existingForm = row.nextElementSibling?.querySelector('.inline-comment-form');
    if (existingForm) {
      existingForm.querySelector('textarea').focus();
      return;
    }

    // Create the comment form row - spans all 4 columns
    const formRow = document.createElement('tr');
    formRow.classList.add('inline-comment-form-row');

    const formHtml = `
      <td colspan="4" class="inline-comment-form-container">
        ${this.getCommentFormHtml(lineNumber, side, filePath)}
      </td>
    `;

    formRow.innerHTML = formHtml;

    // Insert after the current row
    row.parentNode.insertBefore(formRow, row.nextSibling);

    // Focus the textarea
    const textarea = formRow.querySelector('textarea');
    textarea.focus();

    // Attach form event listeners
    this.attachFormListeners(formRow, lineNumber, side, filePath);
  },

  getCommentFormHtml(lineNumber, side, filePath) {
    const sideLabel = side === 'LEFT' ? 'old' : 'new';
    const sideColor = side === 'LEFT' ? '#ef4444' : '#22c55e';

    return `
      <div class="inline-comment-form">
        <div class="inline-comment-form-header">
          <span class="inline-comment-side-badge" style="background-color: ${sideColor}20; color: ${sideColor};">
            ${sideLabel} line ${lineNumber}
          </span>
          <span class="inline-comment-file-path">${filePath}</span>
        </div>
        <textarea
          class="inline-comment-textarea"
          placeholder="Add a comment..."
          rows="3"
        ></textarea>
        <div class="inline-comment-form-actions">
          <button class="button-primary inline-comment-submit">Add comment</button>
          <button class="button-secondary inline-comment-cancel">Cancel</button>
        </div>
      </div>
    `;
  },

  attachFormListeners(formRow, lineNumber, side, filePath) {
    const submitBtn = formRow.querySelector('.inline-comment-submit');
    const cancelBtn = formRow.querySelector('.inline-comment-cancel');
    const textarea = formRow.querySelector('.inline-comment-textarea');

    submitBtn.addEventListener('click', () => {
      this.submitComment(formRow, textarea.value, lineNumber, side, filePath);
    });

    cancelBtn.addEventListener('click', () => {
      formRow.remove();
    });

    // Submit on Ctrl+Enter / Cmd+Enter
    textarea.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        this.submitComment(formRow, textarea.value, lineNumber, side, filePath);
      }
    });
  },

  async submitComment(formRow, body, lineNumber, side, filePath) {
    if (!body.trim()) {
      return;
    }

    const submitBtn = formRow.querySelector('.inline-comment-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';

    try {
      const url = `/api/organization/${window.organizationName}/${window.repositoryName}/pull_requests/${window.pullRequestId}/comments/create`;

      const response = await api.post(url, {
        body: body,
        line: parseInt(lineNumber),
        side: side,
        path: filePath
      });

      if (response.status === 'success') {
        // Reload the page to show the new comment
        window.location.reload();
      } else {
        alert('Failed to add comment: ' + (response.message || 'Unknown error'));
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add comment';
      }
    } catch (error) {
      console.error('Error adding comment:', error);
      alert('Failed to add comment. Please try again.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Add comment';
    }
  }
};
