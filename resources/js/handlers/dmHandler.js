import { BadgeManager } from "../utils/badgeManager.js";

/**
 * Handler de mensagens diretas (DirectMessageSent).
 * - Quando a DM está ativa no cliente: appendMessage, limpar badge e marcar como lidas no servidor.
 * - Quando a DM não está ativa: aplicar badge localmente.
 *
 * Mantém comportamentos existentes; adiciona apenas a chamada POST /dm/{sender}/read
 * quando o utilizador é o recipient e a thread está ativa.
 */
export function handleDirectMessageEvent(e) {
    try {
        const payload = e?.message ?? e;
        if (!payload) return;

        const sender = String(payload?.sender_id ?? "");
        const recipient = String(payload?.recipient_id ?? "");
        const me = String(window.userId || "");

        if (!sender || !recipient || !me) return;
        if (me === sender) return; // ignora mensagens próprias

        // O "peer" da conversa é sempre o outro utilizador
        const peerId = me === sender ? recipient : sender;

        const inDmView = typeof window.appendMessage === "function";
        const activePeer = String(window.peerId || "");
        const isActiveThread = inDmView && activePeer && activePeer === peerId;
        const isRecipient = me === recipient;

        if (isActiveThread) {
            // Mostrar e limpar badge localmente
            BadgeManager.clearBadge("dm", peerId);
            window.appendMessage(payload);

            // Marcar como lidas no servidor para sincronizar unread_count
            if (isRecipient) {
                const token = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                fetch(`/dm/${sender}/read`, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": token,
                        Accept: "application/json",
                    },
                }).catch(() => {});
            }
            return;
        }

        // Fora da thread ativa → aplicar badge local
        BadgeManager.applyBadge("dm", peerId);
    } catch (err) {
        console.warn("dm handler error", err);
    }
}
