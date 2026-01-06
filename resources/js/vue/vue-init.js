import { createApp } from "vue";
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";

// Vue components
import BotFlowBuilder from "../components/BotFlowBuilder.vue";
import ThemeStyleSettings from "../theme/components/ThemeStyleSettings.vue";
import ThemeStyleSwatch from "../theme/components/ThemeStyleSwatch.vue";
import WhatsAppTemplateManager from "../dynamic-template/components/WhatsAppTemplateManager.vue";
import InitiateTemplate from "../initiate-template/InitiateTemplate.vue";
import TemplateBotManager from "../template-bot/TemplateBotManager.vue";
import InitiateChatTemplate from "../initiate-template/chat/InitiateChatTemplate.vue";
// Store Vue app instances for dynamic mounting
const vueAppInstances = {};

// 🔹 Mount Initiate Template
window.mountInitiateTemplate = function () {
    const id = "initiate-template";
    const el = document.getElementById(id);
    if (!el || vueAppInstances[id]) return;
    const app = createApp({});
    app.component("v-select", vSelect);
    app.component("initiate-template", InitiateTemplate);
    app.mount(`#${id}`);

    vueAppInstances[id] = app;
};

// 🔹 Mount Initiate Chat Template
window.mountInitiateChatTemplate = function () {
    const id = "initiate-chat-template";
    const el = document.getElementById(id);
    if (!el || vueAppInstances[id]) return;
    const app = createApp({});
    app.component("v-select", vSelect);
    app.component("initiate-chat-template", InitiateChatTemplate);
    app.mount(`#${id}`);

    vueAppInstances[id] = app;
};
// Watch for modal opening and remount Vue
export function initializeVueApps() {
    document.addEventListener("DOMContentLoaded", function () {
        // Bot Flow Builder
        if (document.getElementById("bot-flow-builder")) {
            const app = createApp({});
            app.component("v-select", vSelect);
            app.component("bot-flow-builder", BotFlowBuilder);
            app.mount("#bot-flow-builder");
        }

        // WhatsApp Dynamic Templates
        if (document.getElementById("dynamic-templates")) {
            const app = createApp({});
            app.component("v-select", vSelect);
            app.component("whatsapp-template-manager", WhatsAppTemplateManager);
            app.mount("#dynamic-templates");
        }

        // Theme Style App
        if (document.getElementById("theme-style-app")) {
            const app = createApp({});
            app.component("theme-style-settings", ThemeStyleSettings);
            app.component("theme-style-swatch", ThemeStyleSwatch);
            app.mount("#theme-style-app");
        }
        // Template bot
        if (document.getElementById("template-bot")) {
            const app = createApp({});
            app.component("v-select", vSelect);
            app.component("template-bot", TemplateBotManager);
            app.mount("#template-bot");
        }
        // InitiateTemplate
        if (document.getElementById("initiate-template")) {
            const app = createApp({});
            app.component("v-select", vSelect);
            app.component("initiate-template", InitiateTemplate);
            app.mount("#initiate-template");
        }
    });
}
