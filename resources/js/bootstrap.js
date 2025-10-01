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
});

// ler variáveis globais
function readGlobals() {
    const auth = document.body.dataset?.authId;
    const room = document.body.dataset?.roomId;
    window.userId = auth ? String(parseInt(auth, 10)) : null;
    window.roomId = room ? String(parseInt(room, 10)) : null;
    const el = document.getElementById("dm-app");
    window.peerId =
        el && el.dataset?.peerId
            ? String(parseInt(el.dataset.peerId, 10))
            : null;
    console.log("globals read:", {
        userId: window.userId,
        roomId: window.roomId,
        peerId: window.peerId,
    });
}
readGlobals();

setTimeout(() => {
    readGlobals();
    if (!window.Echo) return console.warn("Echo missing");

    // 🔒 DMs — canal privado do utilizador (badge no recetor, nunca no remetente)
    if (window.userId) {
        window.Echo.private(`dm.${window.userId}`).listen(
            ".DirectMessageSent",
            (e) => {
                try {
                    const sender = String(e.sender_id ?? "");
                    if (!sender) return;
                    if (sender === window.userId) return; // ignora mensagens próprias

                    // aplica badge no contacto
                    if (typeof window.applyPendingBadge === "function") {
                        window.applyPendingBadge(sender);
                        console.log("bootstrap: applyPendingBadge for", sender);
                    } else {
                        const pending = JSON.parse(
                            localStorage.getItem("pendingBadges") || "[]"
                        ).map(String);
                        if (!pending.includes(sender)) {
                            pending.push(sender);
                            localStorage.setItem(
                                "pendingBadges",
                                JSON.stringify(pending)
                            );
                            window.dispatchEvent(
                                new CustomEvent("pendingBadges:updated", {
                                    detail: { sender_id: sender },
                                })
                            );
                        }
                    }

                    // se a conversa estiver aberta, appendMessage
                    const activePeer = window.peerId || null;
                    const isActiveThread =
                        activePeer &&
                        (sender === activePeer ||
                            String(e.recipient_id) === activePeer);
                    if (
                        isActiveThread &&
                        typeof window.appendMessage === "function"
                    ) {
                        window.appendMessage(e);
                    }
                } catch (err) {
                    console.warn("dm listener error", err);
                }
            }
        );
    }

    // 🔒 Notificações de sala — canal privado user.{id} (uma única subscrição com filtro de remetente)
    if (window.userId) {
        window.Echo.private(`user.${window.userId}`).listen(
            ".RoomMessageSent",
            (e) => {
                try {
                    const rid = String(e.room_id ?? "");
                    const sender = String(e.sender_id ?? "");
                    if (!rid) return;

                    // ignora mensagens enviadas pelo próprio utilizador
                    if (sender === window.userId) {
                        console.log(
                            "bootstrap: ignorado RoomMessageSent do próprio user",
                            sender
                        );
                        return;
                    }

                    if (typeof window.applyPendingRoomBadge === "function") {
                        window.applyPendingRoomBadge(rid);
                        console.log(
                            "bootstrap: applyPendingRoomBadge for",
                            rid
                        );
                    } else {
                        const pendingRooms = JSON.parse(
                            localStorage.getItem("pendingRoomBadges") || "[]"
                        ).map(String);
                        if (!pendingRooms.includes(rid)) {
                            pendingRooms.push(rid);
                            localStorage.setItem(
                                "pendingRoomBadges",
                                JSON.stringify(pendingRooms)
                            );
                            window.dispatchEvent(
                                new CustomEvent("pendingRoomBadges:updated", {
                                    detail: { room_id: rid },
                                })
                            );
                        }
                    }
                } catch (err) {
                    console.warn("user channel listener error", err);
                }
            }
        );
    }

    // 🔒 Sala atual — canal privado room.{id} (appendMessage quando dentro da sala)
    if (window.roomId && window.userId) {
        window.Echo.private(`room.${window.roomId}`).listen(
            ".RoomMessageSent",
            (e) => {
                try {
                    if (!e.room_id || e.recipient_id) return;
                    if (String(e.room_id) !== window.roomId) return;
                    if (String(e.sender_id) === window.userId) return;
                    if (typeof window.appendMessage === "function")
                        window.appendMessage(e);
                } catch (err) {
                    console.warn("room listener error", err);
                }
            }
        );
    }
}, 600);
