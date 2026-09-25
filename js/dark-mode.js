(() => {
    const script = document.currentScript;
    const stylesheetId = "marketlink-dark-mode-css";

    if (script && !document.getElementById(stylesheetId)) {
        const stylesheet = document.createElement("link");
        stylesheet.id = stylesheetId;
        stylesheet.rel = "stylesheet";
        stylesheet.href = new URL("../css/dark-mode.css?v=7", script.src).href;
        document.head.appendChild(stylesheet);
    }

    let savedTheme = "light";
    try {
        savedTheme = localStorage.getItem("marketlink-theme") || "light";
    } catch (error) {
        savedTheme = "light";
    }

    if (document.body) {
        document.body.classList.toggle("dark-mode", savedTheme === "dark");
    }

    function setupToggle() {
        document.body.classList.toggle("dark-mode", savedTheme === "dark");

        let toggle = document.getElementById("darkModeToggle");

        if (!toggle) {
            toggle = document.createElement("button");
            toggle.id = "darkModeToggle";
            toggle.className = "dark-mode-toggle";
            toggle.type = "button";
            document.body.appendChild(toggle);
        }

        function syncToggle() {
            const isDark = document.body.classList.contains("dark-mode");
            toggle.textContent = isDark ? "☀️" : "🌙";
            toggle.title = isDark ? "Light Mode" : "Dark Mode";
            toggle.setAttribute("aria-label", isDark ? "Switch to light mode" : "Switch to dark mode");
            toggle.setAttribute("aria-pressed", String(isDark));
        }

        syncToggle();

        if (toggle.dataset.themeToggleReady === "true") {
            return;
        }

        toggle.dataset.themeToggleReady = "true";
        toggle.addEventListener("click", function () {
            const isDark = document.body.classList.toggle("dark-mode");

            try {
                localStorage.setItem("marketlink-theme", isDark ? "dark" : "light");
            } catch (error) {
                // Keep the current page toggle functional when storage is unavailable.
            }

            syncToggle();
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", setupToggle, { once: true });
    } else {
        setupToggle();
    }
})();
