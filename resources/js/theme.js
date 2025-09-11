export default (() => {
  const themeUrl = "https://components.lucasvanbriemen.nl/api/colors?theme=THEME_NAME";
  const selectedTheme = "auto";

  const getTheme = () => {
    if (selectedTheme === "auto") {
      const darkModeMediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
      return darkModeMediaQuery.matches ? "dark" : "light";
    }

    return selectedTheme;
  };

  const applyTheme = async () => {
    document.documentElement.setAttribute("data-theme", getTheme());
    const url = themeUrl.replace("THEME_NAME", getTheme());
    const colors = await window.api.get(url);

    colors.forEach(color => {
      document.documentElement.style.setProperty(`--${color.name}`, color.value);
    });
  };

  return { getTheme, applyTheme };
})();