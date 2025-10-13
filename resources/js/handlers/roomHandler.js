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
        const createdAt = new Date(payload?.created_at).getTime();

        if (!roomId || senderId === String(window.userId)) return;

        const lastRead = parseInt(
            localStorage.getItem(`roomLastRead:${roomId}`) ?? "0"
        );
        if (createdAt <= lastRead) return;

        const currentRoom = window.roomId || null;

        if (currentRoom && String(currentRoom) === roomId) {
            BadgeManager.clearBadge("room", roomId);
            return;
        }

        BadgeManager.applyBadge("room", roomId);
    } catch (err) {
        console.warn("room badge listener error", err);
    }
}
