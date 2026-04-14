document.addEventListener("DOMContentLoaded", function () {
    const manageBtn = document.getElementById("manageBtn");
    const submenu = document.getElementById("manageSubmenu");
    const arrow = document.getElementById("manageArrow");

    // Sync the arrow based on whatever state the anti-flicker script applied
    if (submenu && arrow) {
        const isCurrentlyVisible = window.getComputedStyle(submenu).display === "block";
        arrow.textContent = isCurrentlyVisible ? "▼" : "▶";
    }

    if (manageBtn && submenu) {
        manageBtn.addEventListener("click", function (e) {
            e.preventDefault();
            
            // Check visibility using computed style to be accurate
            const isCurrentlyVisible = window.getComputedStyle(submenu).display === "block";

            if (isCurrentlyVisible) {
                // Use setProperty with important to ensure it overrides the injected <style> from the head
                submenu.style.setProperty('display', 'none', 'important');
                if (arrow) arrow.textContent = "▶";
                localStorage.setItem("manageMenuOpen", "false");
            } else {
                submenu.style.setProperty('display', 'block', 'important');
                if (arrow) arrow.textContent = "▼";
                localStorage.setItem("manageMenuOpen", "true");
            }
        });
    }
});