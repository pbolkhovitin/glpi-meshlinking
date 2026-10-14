/*!
 * MeshCentral link for GLPI — turns MeshCentral remote IDs into clickable links.
 * Configuration is injected by the plugin as <meta name="meshlinking-config" content="{...}">.
 */
(function () {
    "use strict";

    // Raw type keys as used by the core Item_RemoteManagement class.
    var TYPE_LABELS = {
        meshcentral: ["meshcentral"],
        teamviewer: ["teamviewer"],
        anydesk: ["anydesk"],
        litemanager: ["litemanager", "litemanager"],
        supremo: ["supremo", "supremo"],
        rustdesk: ["rustdesk", "rustdesk"]
    };

    function getConfig() {
        var meta = document.querySelector('meta[name="meshlinking-config"]');
        if (!meta) {
            return null;
        }
        try {
            return JSON.parse(meta.getAttribute("content") || "{}");
        } catch (e) {
            return null;
        }
    }

    function buildUrl(template, id) {
        return String(template).split("{id}").join(id);
    }

    function cellMatches(cfg, text) {
        var value = String(text || "").trim().toLowerCase();
        if (value === "") {
            return false;
        }
        for (var i = 0; i < cfg.types.length; i++) {
            var type = String(cfg.types[i]).toLowerCase();
            var labels = TYPE_LABELS[type] || [type];
            if (labels.indexOf(value) !== -1 || type === value) {
                return true;
            }
        }
        return false;
    }

    var DONE_ATTR = "data-meshlinking-done";

    function apply() {
        var cfg = getConfig();
        if (!cfg || !cfg.url_template || !cfg.types || !cfg.types.length) {
            return;
        }

        var cells = document.querySelectorAll("td");
        for (var i = 0; i < cells.length; i++) {
            var cell = cells[i];
            if (cell.hasAttribute(DONE_ATTR)) {
                continue;
            }
            var typeCell = cell.nextElementSibling;
            if (!typeCell || typeCell.tagName !== "TD") {
                continue;
            }
            if (!cellMatches(cfg, typeCell.textContent)) {
                continue;
            }
            // Leave already-linked values (TeamViewer, AnyDesk, ...) untouched.
            if (cell.querySelector("a[href]")) {
                cell.setAttribute(DONE_ATTR, "1");
                continue;
            }
            var id = (cell.textContent || "").trim();
            if (id === "") {
                continue;
            }

            var url = buildUrl(cfg.url_template, id);
            cell.textContent = "";

            var link = document.createElement("a");
            link.href = url;
            link.textContent = id;
            if (cfg.open_new_tab) {
                link.target = "_blank";
                link.rel = "noopener noreferrer";
            }
            cell.appendChild(link);
            cell.setAttribute(DONE_ATTR, "1");
        }
    }

    var scheduled = false;

    function schedule() {
        if (scheduled) {
            return;
        }
        scheduled = true;
        window.setTimeout(function () {
            scheduled = false;
            apply();
        }, 50);
    }

    function start() {
        apply();
        // GLPI loads asset tabs (e.g. "Remote management") via AJAX, so the
        // table rows may appear long after DOMContentLoaded: watch the DOM.
        if (window.MutationObserver) {
            new MutationObserver(schedule).observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", start);
    } else {
        start();
    }
})();
