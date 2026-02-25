(function(){
  const KEY = "medchat_theme";

  function applyTheme(theme){
    document.documentElement.setAttribute("data-theme", theme);
    localStorage.setItem(KEY, theme);
    const btn = document.getElementById("themeToggle");
    if (btn) btn.textContent = (theme === "dark") ? "☀️ Light mode" : "🌙 Dark mode";
  }

  const saved = localStorage.getItem(KEY);
  if (saved === "dark" || saved === "light") {
    applyTheme(saved);
  } else {
    applyTheme("light");
  }

  document.addEventListener("click", (e) => {
    const btn = e.target.closest("#themeToggle");
    if (!btn) return;
    const current = document.documentElement.getAttribute("data-theme") || "light";
    applyTheme(current === "dark" ? "light" : "dark");
  });
})();