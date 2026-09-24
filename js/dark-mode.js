document.addEventListener("DOMContentLoaded", function () {

    let toggle = document.getElementById("darkModeToggle");

    if (!toggle) {
        toggle = document.createElement("button");
        toggle.id = "darkModeToggle";
        toggle.className = "dark-mode-toggle";
        toggle.type = "button";
        toggle.setAttribute("aria-label", "Toggle Dark Mode");
        toggle.title = "Dark Mode";
        document.body.appendChild(toggle);
    }

    if (!document.getElementById("marketlink-dark-mode-style")) {
        const style = document.createElement("style");
        style.id = "marketlink-dark-mode-style";
        style.textContent = `
            body.dark-mode { background: #111714 !important; color: #e8eee9 !important; }
            body.dark-mode .dark-mode-toggle { background: #f4c542 !important; color: #111714 !important; }
            .dark-mode-toggle { position: fixed; right: 25px; bottom: 25px; z-index: 9999; width: 48px; height: 48px; border: 0; border-radius: 50%; background: #198754; color: #fff; cursor: pointer; font-size: 20px; box-shadow: 0 4px 15px rgba(0,0,0,.2); }
            @media (max-width: 600px) { .dark-mode-toggle { right: 15px; bottom: 15px; width: 44px; height: 44px; } }
        `;
        document.head.appendChild(style);
    }

    // Saved theme
    const savedTheme = localStorage.getItem("marketlink-theme");

    if (savedTheme === "dark") {
        document.body.classList.add("dark-mode");

        toggle.innerHTML = "☀️";
        toggle.title = "Light Mode";
    }

    toggle.addEventListener("click", function () {

            document.body.classList.toggle("dark-mode");

            if (document.body.classList.contains("dark-mode")) {
                localStorage.setItem("marketlink-theme", "dark");
                toggle.innerHTML = "☀️";
                toggle.title = "Light Mode";
            } else {
                localStorage.setItem("marketlink-theme", "light");
                toggle.innerHTML = "🌙";
                toggle.title = "Dark Mode";
            }

    });

});
