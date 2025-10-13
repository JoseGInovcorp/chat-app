import { BadgeManager } from "./utils/badgeManager.js";

document.addEventListener("DOMContentLoaded", () => {
    // Reaplicar badges no arranque
    BadgeManager.applyAll();

    // Cross-tab sync
    window.addEventListener("storage", BadgeManager.syncStorage);

    // Delegação de cliques
    document.addEventListener("click", (e) => {
        const dm = e.target.closest(".direct-contact");
        if (dm?.dataset.userId) {
            const peerId = String(dm.dataset.userId);

            // Limpar badge da DM clicada
            BadgeManager.clearBadge("dm", peerId);

            // Atualizar globais: estamos numa DM → limpar roomId
            window.peerId = peerId;
            window.roomId = null;
        }

        const room = e.target.closest(".room-link");
        if (room?.dataset.roomIdLink) {
            const roomId = String(room.dataset.roomIdLink);

            // Limpar badge da sala clicada
            BadgeManager.clearBadge("room", roomId);

            // Atualizar globais: estamos numa sala → limpar peerId
            window.roomId = roomId;
            window.peerId = null;
        }
    });

    // Echo listeners → eventos custom
    window.addEventListener("pendingBadges:updated", (ev) => {
        const id = ev?.detail?.sender_id;
        if (id && String(window.peerId) !== String(id)) {
            BadgeManager.applyBadge("dm", id);
        }
    });

    window.addEventListener("pendingRoomBadges:updated", (ev) => {
        const rid = ev?.detail?.room_id;
        if (rid && String(window.roomId) !== String(rid)) {
            BadgeManager.applyBadge("room", rid);
        }
    });

    // Expor globalmente se necessário
    window.BadgeManager = BadgeManager;
});
