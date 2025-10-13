function updateStorage(key, id, action = "add") {
    const list = JSON.parse(localStorage.getItem(key) || "[]").map(String);
    const newList =
        action === "add"
            ? [...new Set([...list, id])]
            : list.filter((x) => x !== id);
    localStorage.setItem(key, JSON.stringify(newList));
}

export const BadgeManager = {
    applyBadge(type, id) {
        if (!id) return;
        id = String(id);

        const current = type === "dm" ? window.peerId : window.roomId;
        if (String(current) === id) return;

        const selector = `[data-badge-type="${type}"][data-badge-id="${id}"]`;
        const badge = document.querySelector(selector);
        if (badge) badge.classList.remove("hidden");

        updateStorage(
            type === "dm" ? "pendingBadges" : "pendingRoomBadges",
            id,
            "add"
        );
    },

    clearBadge(type, id) {
        if (!id) return;
        id = String(id);

        const selector = `[data-badge-type="${type}"][data-badge-id="${id}"]`;
        const badge = document.querySelector(selector);
        if (badge) badge.classList.add("hidden");

        updateStorage(
            type === "dm" ? "pendingBadges" : "pendingRoomBadges",
            id,
            "remove"
        );
    },

    applyAll() {
        ["pendingBadges", "pendingRoomBadges"].forEach((key) => {
            const type = key === "pendingBadges" ? "dm" : "room";
            const current = type === "dm" ? window.peerId : window.roomId;
            const list = JSON.parse(localStorage.getItem(key) || "[]").map(
                String
            );

            list.forEach((id) => {
                if (String(current) === id) {
                    updateStorage(key, id, "remove");
                    return;
                }
                const selector = `[data-badge-type="${type}"][data-badge-id="${id}"]`;
                const badge = document.querySelector(selector);
                if (badge) badge.classList.remove("hidden");
            });
        });
    },

    syncStorage(e) {
        if (!["pendingBadges", "pendingRoomBadges"].includes(e.key)) return;
        const type = e.key === "pendingBadges" ? "dm" : "room";
        const current = type === "dm" ? window.peerId : window.roomId;
        const list = JSON.parse(e.newValue || "[]").map(String);

        list.forEach((id) => {
            if (String(current) === id) {
                updateStorage(e.key, id, "remove");
                return;
            }
            const selector = `[data-badge-type="${type}"][data-badge-id="${id}"]`;
            const badge = document.querySelector(selector);
            if (badge) badge.classList.remove("hidden");
        });
    },
};
