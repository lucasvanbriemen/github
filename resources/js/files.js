export default {
  IS_EDITING: false,

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

    const allFileHeaders = document.querySelectorAll(".diff-file-header");
    allFileHeaders.forEach(fileHeader => {
      fileHeader.addEventListener("click", () => this.toggleFileViewed(fileHeader.getAttribute("data-file")));
    });
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

  toggleFileViewed(file) {
    const fileElement= document.querySelector(`.diff-file[data-file="${file}"]`);
    if (fileElement.classList.contains("viewed")) {
      this.markFileNotViewed(file);
    } else {
      this.markFileViewed(file);
    }
  },

  markFileViewed(file) {
    const url = window.location.origin + "/api/organization/" + window.organizationName + "/" + window.repositoryName + "/pull_requests/" + window.pullRequestId + "/files/" + "viewed?file=" + encodeURIComponent(file);
    const fileElement = document.querySelector(`.diff-file[data-file="${file}"]`);
    fileElement.classList.add("viewed");
    api.get(url, {}, true).then(data => {
      fileElement.classList.add("viewed");
    });
  },

  markFileNotViewed(file) {
    const url = window.location.origin + "/api/organization/" + window.organizationName + "/" + window.repositoryName + "/pull_requests/" + window.pullRequestId + "/files/" +"not_viewed?file=" + encodeURIComponent(file);
    const fileElement = document.querySelector(`.diff-file[data-file="${file}"]`);
    fileElement.classList.remove("viewed");
    api.get(url, {}, true).then(data => {
      fileElement.classList.remove("viewed");
    });
  },

};
