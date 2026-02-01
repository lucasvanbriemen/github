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
