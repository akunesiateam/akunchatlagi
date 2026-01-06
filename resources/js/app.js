import '../../vendor/filament/tables/resources/js/index.js';
import "./bootstrap";
import "./config";
import "./alpine-init";
import "@nextapps-be/livewire-sortablejs";
import "./../../vendor/power-components/livewire-powergrid/dist/powergrid";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";
import "./tippy";
import GLightbox from "glightbox";
import { refreshThemeStyle } from "./theme/refreshTheme";
import { initializeVueApps } from "./vue/vue-init"; // 👈 new import

// Initialize Vue apps
initializeVueApps();

// Initialize theme refresh handler
refreshThemeStyle();

// Store the lightbox instance globally
window.GLightboxInstance = null;

// Define a global function to initialize GLightbox
window.initGLightbox = function () {
    if (window.GLightboxInstance) {
        window.GLightboxInstance.destroy();
    }

    window.GLightboxInstance = GLightbox({
        selector: ".glightbox",
        touchNavigation: true,
        loop: true,
        zoomable: true,
        autoplayVideos: true,
    });
};

// Initialize once on DOM ready
document.addEventListener("DOMContentLoaded", function () {
    window.initGLightbox();
});
