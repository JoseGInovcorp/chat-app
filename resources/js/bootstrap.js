import Echo from "laravel-echo";
import Pusher from "pusher-js";
window.Pusher = Pusher;

import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.withCredentials = true;

const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");
if (token) window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    auth: { headers: { "X-CSRF-TOKEN": token }, withCredentials: true },
    namespace: null, // Alinha com broadcastAs() sem prefixo
});

// 🔎 Ler variáveis globais
function readGlobals() {
    const auth = document.body?.dataset?.authId;
    const roomEl = document.getElementById("room-app");
    const room = roomEl?.dataset?.roomId;
    const dmEl = document.getElementById("dm-app");
    const peer = dmEl?.dataset?.peerId;

    window.userId = auth ? String(parseInt(auth, 10)) : null;
    window.roomId = room ? String(parseInt(room, 10)) : null;
    window.peerId = peer ? String(parseInt(peer, 10)) : null;

    console.log("globals:", {
        userId: window.userId,
        roomId: window.roomId,
        peerId: window.peerId,
    });
    return !!window.userId;
}

// 🔌 Logs de conexão
if (window.Echo?.connector?.pusher?.connection) {
    window.Echo.connector.pusher.connection.bind("connected", () =>
        console.log("[echo] connected")
    );
    window.Echo.connector.pusher.connection.bind("error", (err) =>
        console.warn("[echo] error", err)
    );
    window.Echo.connector.pusher.connection.bind("state_change", (state) =>
        console.log("[echo] state", state)
    );
}

// 🧠 Inicializa listeners quando globals estiverem prontos
function initListenersOnce() {
    if (initListenersOnce.done) return;
    if (!readGlobals()) return;

    // ---------------------------
    // Handler reutilizável DMs
    // ---------------------------
    function handleDirectMessageEvent(e) {
        try {
            console.log("[DM EVENT RAW]", e);
            const payload = e?.message ?? e;
            const sender = String(payload?.sender_id ?? "");
            const recipient = String(payload?.recipient_id ?? "");

            if (!sender) return;

            const activePeer = window.peerId || null;
            const isActiveThread =
                activePeer &&
                (sender === activePeer || recipient === activePeer);

            if (!isActiveThread) {
                // Sidebar: badge DM
                window.dispatchEvent(
                    new CustomEvent("pendingBadges:updated", {
                        detail: { sender_id: sender },
                    })
                );
                if (typeof window.applyPendingBadge === "function") {
                    window.applyPendingBadge(sender);
                }
                console.log("[badge:event] DM from", sender);
            } else {
                // Se temos a thread aberta, garantir que não há badge pendente
                if (typeof window.clearPendingBadge === "function") {
                    window.clearPendingBadge(sender);
                }
            }

            // Append se a conversa está aberta
            if (isActiveThread && typeof window.appendMessage === "function") {
                window.appendMessage(payload);
                // Garantir que após append não há badge
                if (typeof window.clearPendingBadge === "function") {
                    window.clearPendingBadge(sender);
                }
            }
        } catch (err) {
            console.warn("dm handler error", err);
        }
    }

    // ---------------------------
    // DMs — user.{userId}
    // ---------------------------
    if (window.userId) {
        const chUser = window.Echo.private(`user.${window.userId}`);

        // listener principal com nome curto (se broadcastAs estiver a funcionar)
        chUser.listen("DirectMessageSent", handleDirectMessageEvent);

        // aliases seguros para variações de namespace/nome de evento
        chUser.listen(
            ".App\\Events\\DirectMessageSent",
            handleDirectMessageEvent
        );
        chUser.listen(
            ".App.Events.DirectMessageSent",
            handleDirectMessageEvent
        );
    }

    // ---------------------------
    // Notificações globais — user.{userId} (salas)
    // ---------------------------
    if (window.userId) {
        window.Echo.private(`user.${window.userId}`).listen(
            "RoomMessageSent",
            (e) => {
                try {
                    const payload = e?.message ?? e;
                    const roomId = String(payload?.room_id ?? "");
                    const senderId = String(payload?.sender_id ?? "");
                    if (!roomId) return;
                    if (senderId === String(window.userId)) return;

                    // Se o utilizador está naquela sala aberta, não mostra badge
                    const currentRoom = window.roomId || null;
                    if (currentRoom && String(currentRoom) === roomId) {
                        // Limpar badge por segurança
                        if (
                            typeof window.clearPendingRoomBadge === "function"
                        ) {
                            window.clearPendingRoomBadge(roomId);
                        }
                        return;
                    }

                    // Caso contrário, notificar como pendente
                    window.dispatchEvent(
                        new CustomEvent("pendingRoomBadges:updated", {
                            detail: { room_id: roomId },
                        })
                    );
                    if (typeof window.applyPendingRoomBadge === "function") {
                        window.applyPendingRoomBadge(roomId);
                    }
                    console.log("[badge:event] ROOM", roomId);
                } catch (err) {
                    console.warn("room badge listener error", err);
                }
            }
        );
    }

    // ---------------------------
    // Sala atual — room.{roomId} (append ao abrir sala)
    // ---------------------------
    if (window.roomId && window.userId) {
        window.Echo.private(`room.${window.roomId}`).listen(
            "RoomMessageSent",
            (e) => {
                try {
                    const payload = e?.message ?? e;
                    const roomId = String(payload?.room_id ?? "");
                    const senderId = String(payload?.sender_id ?? "");
                    if (roomId !== String(window.roomId)) return;
                    if (senderId === String(window.userId)) return;

                    if (typeof window.appendMessage === "function") {
                        window.appendMessage(payload);
                    }
                } catch (err) {
                    console.warn("room listener error", err);
                }
            }
        );
    }

    initListenersOnce.done = true;
    console.log("[echo] listeners initialized");
}

document.addEventListener("DOMContentLoaded", () => initListenersOnce());

const tryInitInterval = setInterval(() => {
    if (initListenersOnce.done) clearInterval(tryInitInterval);
    else initListenersOnce();
}, 200);
setTimeout(() => clearInterval(tryInitInterval), 5000);
