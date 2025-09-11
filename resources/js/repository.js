export default (() => {
  const path = window.location.pathname;
  api.get(window.location.origin + `/api${path}/`).then((data) => {
    console.log(data);
    document.querySelector(".file-list").innerHTML = data;
  });

  const fileList = document.querySelector(".file-list");
  fileList.addEventListener("click", (event) => {
    if (event.target.classList.contains("file")) {
      event.preventDefault();
      const url = new URL(event.target.href);
      history.pushState({}, "", url);
      api.get(window.location.origin + `/api${url.pathname}/`).then((data) => {
        console.log(data);
        document.querySelector(".file-list").innerHTML = data;
      });
    }
  });
});