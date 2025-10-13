import Echo from "laravel-echo";
import Pusher from "pusher-js";
import axios from "axios";
import { handleDirectMessageEvent } from "./handlers/dmHandler";
import { handleRoomMessageEvent } from "./handlers/roomHandler"; // ✅ reposto

window.Pusher = Pusher;
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
    namespace: null,
});

// Ler variáveis globais
function readGlobals() {
    const auth = document.body?.dataset?.authId;
    const roomEl = document.getElementById("room-app");
    const dmEl = document.getElementById("dm-app");

    window.userId = auth ? String(parseInt(auth, 10)) : null;
    window.roomId = roomEl?.dataset?.roomId ?? null;
    window.peerId = dmEl?.dataset?.peerId ?? null;

    return !!window.userId;
}

// Inicialização de listeners (executa apenas uma vez)
function initListenersOnce() {
    if (initListenersOnce.done) return;
    if (!readGlobals()) return;

    if (window.userId) {
        const chUser = window.Echo.private(`user.${window.userId}`);
        chUser.listen("DirectMessageSent", handleDirectMessageEvent);
        chUser.listen(
            ".App\\Events\\DirectMessageSent",
            handleDirectMessageEvent
        );
        chUser.listen(
            ".App.Events.DirectMessageSent",
            handleDirectMessageEvent
        );

        // ✅ Repor escuta do evento de sala
        chUser.listen("RoomMessageSent", handleRoomMessageEvent);
        chUser.listen(".App\\Events\\RoomMessageSent", handleRoomMessageEvent);
        chUser.listen(".App.Events.RoomMessageSent", handleRoomMessageEvent);
    }

    initListenersOnce.done = true;
    if (import.meta.env.DEV) console.log("[echo] listeners initialized");
}

// Bootstrap
document.addEventListener("DOMContentLoaded", () => initListenersOnce());
