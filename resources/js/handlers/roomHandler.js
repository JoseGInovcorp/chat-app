import { BadgeManager } from "../utils/badgeManager.js";

/**
 * Handler de mensagens de sala.
 * Responsável apenas por gerir badges quando chegam eventos de RoomMessageSent
 * através do canal privado do utilizador (user.{id}).
 */
export function handleRoomMessageEvent(e) {
    try {
        const payload = e?.message ?? e;
        const roomId = String(payload?.room_id ?? "");
        const senderId = String(payload?.sender_id ?? "");

        if (!roomId || senderId === String(window.userId)) return;

        const currentRoom = window.roomId || null;

        // Se estou na sala ativa → limpar badge (a mensagem já é appendada pelo room.js)
        if (currentRoom && String(currentRoom) === roomId) {
            BadgeManager.clearBadge("room", roomId);
            return;
        }

        // Caso contrário → aplicar badge
        BadgeManager.applyBadge("room", roomId);
    } catch (err) {
        console.warn("room badge listener error", err);
    }
}
