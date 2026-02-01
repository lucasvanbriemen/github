/**
 * Filter comments by reviewer - returns flat list of all comments by reviewer
 * @param {Array} allComments - All comments from the item
 * @param {number|string} reviewerUserId - The user ID of the reviewer
 * @param {boolean} includeResolved - Whether to include resolved comments (default: false)
 * @returns {Array} Flat list of all comments by the specified reviewer
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

    // Add this comment if by reviewer
    if (comment.author?.id === reviewerUserId) {
      filtered.push(comment);
    }

    // Recursively process child comments
    if (comment.child_comments?.length > 0) {
      comment.child_comments.forEach(processComment);
    }
  };

  allComments.forEach(processComment);
  return filtered;
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
