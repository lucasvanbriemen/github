/**
 * Filter comments by reviewer
 * @param {Array} allComments - All comments from the item
 * @param {number|string} reviewerUserId - The user ID of the reviewer
 * @param {boolean} includeResolved - Whether to include resolved comments (default: false)
 * @returns {Array} Comments by the specified reviewer
 */
export function filterCommentsByReviewer(allComments, reviewerUserId, includeResolved = false) {
  if (!allComments || !reviewerUserId) {
    return [];
  }

  const filtered = [];
  const processComment = (comment) => {
    // Skip if resolved and we're not including resolved comments
    if (comment.resolved && !includeResolved) {
      return;
    }

    // Check if this comment is by the reviewer
    const isByReviewer = comment.author?.id === reviewerUserId;

    // Check if any child comment is by the reviewer (recursively)
    const hasChildByReviewer = comment.child_comments?.some(child => {
      return hasReviewerComment(child, reviewerUserId, includeResolved);
    });

    if (isByReviewer) {
      filtered.push(comment);
    } else if (hasChildByReviewer) {
      // If a child is by the reviewer, include this parent comment
      filtered.push(comment);
    }
  };

  allComments.forEach(processComment);
  return filtered;
}

/**
 * Check if a comment thread contains comments by the reviewer (recursive helper)
 * @private
 */
function hasReviewerComment(comment, reviewerUserId, includeResolved) {
  if (comment.resolved && !includeResolved) {
    return false;
  }

  if (comment.author?.id === reviewerUserId) {
    return true;
  }

  return comment.child_comments?.some(child =>
    hasReviewerComment(child, reviewerUserId, includeResolved)
  ) || false;
}

/**
 * Build comment context with parent and children
 * @param {Object} comment - The comment to build context for
 * @param {number|string} reviewerUserId - The user ID of the reviewer
 * @param {boolean} includeResolved - Whether to include resolved comments
 * @returns {Object} Comment with full context
 */
export function buildCommentContext(comment, reviewerUserId, includeResolved = false) {
  if (!comment) {
    return null;
  }

  const contextComment = {
    ...comment,
    parentComment: comment.in_reply_to_id ? null : undefined,
    childCommentsByReviewer: []
  };

  // Filter child comments to show only reviewer's children
  if (comment.child_comments && comment.child_comments.length > 0) {
    contextComment.childCommentsByReviewer = comment.child_comments.filter(child => {
      if (child.resolved && !includeResolved) {
        return false;
      }
      return child.author?.id === reviewerUserId;
    });
  }

  return contextComment;
}

/**
 * Get parent comment reference if this is a reply
 * @param {Object} comment - The comment
 * @param {Array} allComments - All comments to search in
 * @returns {Object|null} Parent comment or null
 */
export function getParentComment(comment, allComments) {
  if (!comment.in_reply_to_id || !allComments) {
    return null;
  }

  const findParent = (comments) => {
    for (const c of comments) {
      if (c.id === comment.in_reply_to_id) {
        return c;
      }
      if (c.child_comments?.length > 0) {
        const found = findParent(c.child_comments);
        if (found) return found;
      }
    }
    return null;
  };

  return findParent(allComments);
}
