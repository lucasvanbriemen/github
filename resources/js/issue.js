export default {
  init() {
    const resolveButtons = document.querySelectorAll(".resolve-comment");
    const unresolveButtons = document.querySelectorAll(".unresolve-comment");

    unresolveButtons.forEach(button => {
      button.addEventListener("click", () => this.updateComment(button.getAttribute("data-comment"), button.getAttribute("data-url")));
    });

    resolveButtons.forEach(button => {
      button.addEventListener("click", () => this.updateComment(button.getAttribute("data-comment"), button.getAttribute("data-url")));
    });

    const allCommentsHeaders = document.querySelectorAll(".issue-comment .comment-header");
    allCommentsHeaders.forEach(commentHeader => {
      commentHeader.addEventListener("click", () => this.openResolvedComments(commentHeader.closest(".issue-comment").getAttribute("data-comment")));
    });
  },

  updateComment(id, url) {
    const comment = document.querySelector(`.issue-comment[data-comment="${id}"]`);
    if (!comment) {
      return;
    }

    api.patch(url).then(data => {
      comment.classList.toggle("resolved", data.resolved);
    });
  },

  openResolvedComments(id) {
    const comment = document.querySelector(`.issue-comment[data-comment="${id}"]`);
    comment.classList.toggle("resolved");
    app.setLoading(false);
  }
};
