import app from "./app";

const modules = import.meta.glob('./**/*.js', { eager: true });

for (const [path, module] of Object.entries(modules)) {
  if (path === './root.js') continue;
  
  const filename = path.split('/').pop().replace('.js', '');
  window[filename] = module.default;
}

theme.applyTheme();
app.setLoading(false);

if (window.start) {
  if (Array.isArray(window.start)) {
    window.start.forEach((item) => {
      window[item].init();
    });
  } else {
    window[window.start].init();
  }
}
