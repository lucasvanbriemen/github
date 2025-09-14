export default {
  baseURL: "https://components.lucasvanbriemen.nl/api",

  init() {
    const compoments = document.querySelectorAll('[data-component]');
    console.log('Found components:', compoments);
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
    fetch(`${this.baseURL}/${name}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
    })
    .then(response => response.json())
    .then(data => {
      target.innerHTML = data.view;
      eval(data.js);

      if (data.scss) {
        const style = document.createElement('style');
        style.textContent = data.scss;
        document.head.appendChild(style);
      }

      target.setAttribute('data-component-loaded', 'loaded');
    })
    .catch(error => {
      console.error('Error loading component:', error);
      target.setAttribute('data-component-loaded', 'error');
    });
  }
};
