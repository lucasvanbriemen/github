export default {
  init() {
    const path = window.location.pathname;
    window.api.get(window.location.origin + `/api${path}/`).then((data) => {
      console.log(data);
      document.querySelector(".file-list").innerHTML = data;
    });

    const fileList = document.querySelector(".file-list");
    fileList.addEventListener("click", this.updateFileList);
  },

  updateFileList(event) {
    if (event.target.classList.contains("file")) {
      event.preventDefault();
      app.setUrl(event.target.dataset.url);
      window.api.get(event.target.dataset.apiUrl).then((data) => {
        document.querySelector(".file-list").innerHTML = data;
      });
    }
  }
};