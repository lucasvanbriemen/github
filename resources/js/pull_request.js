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

    this.getLinkedIssues(window.pullRequestId);
  },

  updateComment(id, url) {
    const comment = document.querySelector(`.issue-comment[data-comment="${id}"]`);
    if (!comment) {
      return;
    }

    api.patch(url).then(data => {
      comment.classList.toggle("resolved", data.resolved);
      comment.querySelector(".button-primary").innerHTML = data.resolved ? "Mark as unresolved" : "Mark as resolved";

      const newURL = data.resolved ? url.replace("/resolve", "/unresolve") : url.replace("/unresolve", "/resolve");
      comment.querySelector(".button-primary").setAttribute("data-url", newURL);
    });
  },

  openResolvedComments(id) {
    const comment = document.querySelector(`.issue-comment[data-comment="${id}"]`);
    comment.classList.toggle("resolved");
  },

  getLinkedIssues(id) {
    const url = window.location.origin + "/api/organization/" + window.organizationName + "/" + window.repositoryName + "/pull_requests/" + id + "/linked_issues";
    api.get(url).then((data) => {
      const issues = document.querySelector(".linked-issues");
      issues.innerHTML = data
    });
  }
};
