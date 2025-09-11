export default (() => {
  const path = window.location.pathname;
  api.get(window.location.origin + `/api${path}/`).then((data) => {
    console.log(data);
    document.querySelector(".file-list").innerHTML = data;
  });
});