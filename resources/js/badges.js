// resources/js/badges.js — versão mínima

window.applyPendingBadge = function ({ type, id }) {
    try {
        const tid = String(type);
        const bid = String(id);
        const selector = `[data-badge-type="${tid}"][data-badge-id="${bid}"]`;
        const dot = document.querySelector(selector);

        console.log("applyPendingBadge →", {
            type: tid,
            id: bid,
            found: !!dot,
            selector,
        });

        if (!dot) return; // não encontrado: não faz nada
        dot.classList.remove("hidden");
        dot.classList.add("animate-ping");
    } catch (err) {
        console.warn("applyPendingBadge error", err);
    }
};
