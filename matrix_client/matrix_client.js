(function () {
    "use strict";

    const CONTAINER_ID = "matrix-chat-container";
    const WIDGET_COLLAPSED_KEY = "matrix_widget_collapsed";
    const HYDROGEN_URL = new URL("hydrogen/index.html", getAddonBaseUrl()).href;

    function getAddonBaseUrl() {
        const script = document.currentScript;
        if (script && script.src) {
            return new URL(".", script.src);
        }

        const path = window.location.pathname.replace(/\/+$/, "");
        const addonIndex = path.indexOf("/addon/");
        if (addonIndex >= 0) {
            return new URL(path.slice(0, addonIndex) + "/addon/matrix_client/", window.location.origin);
        }
        return new URL("/addon/matrix_client/", window.location.origin);
    }

    function readCollapsedPreference() {
        try {
            return window.localStorage.getItem(WIDGET_COLLAPSED_KEY) !== "false";
        } catch (error) {
            return true;
        }
    }

    function saveCollapsedPreference(collapsed) {
        try {
            window.localStorage.setItem(WIDGET_COLLAPSED_KEY, String(collapsed));
        } catch (error) {
        }
    }

    function createWidget() {
        const container = document.createElement("div");
        container.id = CONTAINER_ID;
        container.className = readCollapsedPreference() ? "collapsed" : "";

        const header = document.createElement("div");
        header.id = "matrix-chat-trigger-header";
        header.setAttribute("role", "button");
        header.setAttribute("tabindex", "0");
        header.setAttribute("aria-expanded", String(!container.classList.contains("collapsed")));

        const label = document.createElement("span");
        label.textContent = "💬 Matrix Chat";

        const toggleIcon = document.createElement("span");
        toggleIcon.className = "toggle-icon";
        toggleIcon.textContent = "▼";
        toggleIcon.setAttribute("aria-hidden", "true");

        header.append(label, toggleIcon);

        const wrapper = document.createElement("div");
        wrapper.id = "matrix-chat-iframe-wrapper";

        const iframe = document.createElement("iframe");
        iframe.src = HYDROGEN_URL;
        iframe.title = "Matrix Chat";
        iframe.setAttribute("allow", "clipboard-write; camera; microphone; display-capture");
        iframe.setAttribute("loading", "eager");
        iframe.setAttribute("scrolling", "yes");
        iframe.setAttribute("referrerpolicy", "same-origin");

        wrapper.appendChild(iframe);
        container.append(header, wrapper);

        function toggle() {
            container.classList.toggle("collapsed");
            const collapsed = container.classList.contains("collapsed");
            header.setAttribute("aria-expanded", String(!collapsed));
            saveCollapsedPreference(collapsed);
        }

        header.addEventListener("click", toggle);
        header.addEventListener("keydown", function (event) {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                toggle();
            }
        });

        return container;
    }

    function ensureWidget() {
        if (!document.body || document.getElementById(CONTAINER_ID)) {
            return;
        }
        document.body.appendChild(createWidget());
    }

    function start() {
        ensureWidget();

        const observer = new MutationObserver(function () {
            ensureWidget();
        });
        observer.observe(document.body, { childList: true });

        window.__matrixClientWidgetObserver = window.__matrixClientWidgetObserver || observer;
        if (window.__matrixClientWidgetObserver !== observer) {
            observer.disconnect();
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", start, { once: true });
    } else {
        start();
    }
})();
