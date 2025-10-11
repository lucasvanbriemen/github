import markdownEditor from './markdown_editor.js';

export default {
  IS_EDITING: false,

  init() {
    // Initialize markdown editor
    markdownEditor.init();
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

    this.getLinkedIssues(window.pullRequestNumber);

    // Initialize visibility based on data-editing attributes
    document.querySelectorAll('*[data-editing="0"]').forEach(el => el.style.display = "block");
    document.querySelectorAll('*[data-editing="1"]').forEach(el => el.style.display = "none");

    [".edit-pr", ".cancel-edit", ".save-edit"].forEach((selector, i) => {
      const el = document.querySelector(selector);
      // i === 2 is the save button (we want to save)
      el.addEventListener("click", () => this.toggleEditMode(i === 2));
    });

    const mergeButton = document.querySelector(".merge-pr");
    const closeButton = document.querySelector(".close-pr");
    mergeButton.addEventListener("click", () => this.mergePullRequest());
    closeButton.addEventListener("click", () => this.closePullRequest());

    if (mergeButton) {
      mergeButton.addEventListener("click", () => this.mergePullRequest());
    }

    if (closeButton) {
      closeButton.addEventListener("click", () => this.closePullRequest());
    }
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
    api.get(url, {}, true).then((data) => {
      const issues = document.querySelector(".linked-issues");
      issues.innerHTML = data
    });
  },

  toggleEditMode(triggerSave = false) {
    if (triggerSave) {
      this.updatePullRequest();
    }

    this.IS_EDITING = !this.IS_EDITING;

    const displayTitle = document.getElementById("pr-title");
    const displayBody = document.getElementById("pr-body");

    const editTitle = document.getElementById("edit-pr-title");
    const editBodyTextarea = markdownEditor.getTextarea("edit-pr-body");

    editTitle.value = displayTitle.getAttribute("data-raw");

    // Set value in markdown editor
    if (editBodyTextarea) {
      markdownEditor.setValue("edit-pr-body", displayBody.getAttribute("data-raw"));
    }

    if (this.IS_EDITING) {
      document.querySelectorAll('*[data-editing="0"]').forEach(el => el.style.display = "none");
      document.querySelectorAll('*[data-editing="1"]').forEach(el => el.style.display = "block");
    } else {
      document.querySelectorAll('*[data-editing="0"]').forEach(el => el.style.display = "block");
      document.querySelectorAll('*[data-editing="1"]').forEach(el => el.style.display = "none");
    }
  },

  updatePullRequest() {
    const title = document.getElementById("edit-pr-title").value;
    const body = markdownEditor.getValue("edit-pr-body");

    const displayTitle = document.getElementById("pr-title");
    const displayBody = document.getElementById("pr-body");

    api.patch(`/api/organization/${window.organizationName}/${window.repositoryName}/pull_requests/${window.pullRequestNumber}/edit`, {
      title,
      body
    }).then(data => {
      // Update data-raw attributes for next edit
      displayTitle.setAttribute("data-raw", title);
      displayBody.setAttribute("data-raw", body);
    });
  },

  mergePullRequest() {
    api.put(`/api/organization/${window.organizationName}/${window.repositoryName}/pull_requests/${window.pullRequestNumber}/merge`, {})
      .then(data => {
        window.location.reload();
      });
  },

  closePullRequest() {
    api.patch(`/api/organization/${window.organizationName}/${window.repositoryName}/pull_requests/${window.pullRequestNumber}/close`, {})
      .then(data => {
        window.location.reload();
      });
  }
};
