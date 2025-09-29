import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// --- Echo + Pusher ---
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? "localhost",
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    forceTLS: false,
    disableStats: true,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? "mt1",
});

// Listener de teste
window.Echo.channel("test-channel").listen(".test-event", (e) => {
    console.log("Recebido do Soketi:", e.message);
});

// Para mensagens em salas
/*window.Echo.private(`room.${roomId}`).listen("RoomMessageSent", (e) => {
    console.log("Nova mensagem na sala:", e);
});

// Para mensagens diretas (DM)
window.Echo.private(`dm.${userId}`).listen("DirectMessageSent", (e) => {
    console.log("Nova DM:", e);
});*/
