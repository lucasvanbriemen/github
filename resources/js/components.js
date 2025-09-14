export default {
  baseURL: "https://components.lucasvanbriemen.nl/api",

  init() {
    const compoments = document.querySelectorAll('[data-component]');
    compoments.forEach(compoment => {
      const name = compoment.getAttribute('data-component');
      const data = compoment.getAttribute('data-component-data');
      const dataObj = data ? JSON.parse(data) : {};
      
      compoment.innerHTML = '';
      compoment.setAttribute('data-component-loaded', 'loading');
      
      this.renderComponent(name, compoment, dataObj);
    });
  },

  renderComponent(name, target, data = {}) {
    api.get(`${this.baseURL}/${name}`)
    .then(data => {
      target.innerHTML = data.view;
      eval(data.js);

      const style = document.createElement('style');
      style.textContent = data.scss;
      document.head.appendChild(style);

      target.setAttribute('data-component-loaded', 'loaded');
      app.setLoading(false);
    })
  }
};
