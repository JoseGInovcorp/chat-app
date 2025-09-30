import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Axios + CSRF (necessário para /broadcasting/auth)
import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

if (token) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
    console.log("✅ CSRF token configurado");
} else {
    console.warn("⚠️ CSRF token não encontrado");
}

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});

// --- Listener de teste ---
window.Echo.channel("test-public").listen("TestEvent", (e) => {
    console.log("📡 Recebido no canal público:", e);
});

// --- Subscrição atrasada para garantir sessão válida ---
setTimeout(() => {
    if (!window.Echo) {
        console.warn("❌ Echo não está disponível");
        return;
    }

    if (window.roomId && window.userId) {
        console.log(`🔒 Subscrição privada: room.${window.roomId}`);
        window.Echo.private(`room.${window.roomId}`).listen(
            "RoomMessageSent",
            (e) => {
                if (e.sender_id === window.userId) {
                    console.log("🛑 Ignorado: mensagem do próprio utilizador");
                    return;
                }

                console.log("💬 Nova mensagem na sala:", e);
                if (typeof window.appendMessage === "function") {
                    window.appendMessage(e);
                }
            }
        );
    } else {
        console.warn("⚠️ Variáveis globais não definidas: roomId ou userId");
    }

    if (window.userId) {
        console.log(`🔒 Subscrição privada: dm.${window.userId}`);
        window.Echo.private(`dm.${window.userId}`).listen(
            "DirectMessageSent",
            (e) => {
                if (e.sender_id === window.userId) {
                    console.log("🛑 Ignorado: DM do próprio utilizador");
                    return;
                }

                console.log("📨 Nova DM recebida:", e);

                // ✅ Atualiza visualmente o badge na sidebar
                const contactItem = document.querySelector(
                    `[data-user-id="${e.sender_id}"]`
                );
                if (contactItem) {
                    contactItem.classList.add("has-unread");
                }

                // ✅ Opcional: atualiza a conversa se estiver aberta
                if (typeof window.appendMessage === "function") {
                    window.appendMessage(e);
                }
            }
        );
    }
}, 1000); // espera 1 segundo para garantir que sessão está pronta
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Axios + CSRF (necessário para /broadcasting/auth)
import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

if (token) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
    console.log("✅ CSRF token configurado");
} else {
    console.warn("⚠️ CSRF token não encontrado");
}

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});

// --- Listener de teste ---
window.Echo.channel("test-public").listen("TestEvent", (e) => {
    console.log("📡 Recebido no canal público:", e);
});

// --- Subscrição atrasada para garantir sessão válida ---
setTimeout(() => {
    if (!window.Echo) {
        console.warn("❌ Echo não está disponível");
        return;
    }

    if (window.roomId && window.userId) {
        console.log(`🔒 Subscrição privada: room.${window.roomId}`);
        window.Echo.private(`room.${window.roomId}`).listen(
            "RoomMessageSent",
            (e) => {
                if (e.sender_id === window.userId) {
                    console.log("🛑 Ignorado: mensagem do próprio utilizador");
                    return;
                }

                console.log("💬 Nova mensagem na sala:", e);
                if (typeof window.appendMessage === "function") {
                    window.appendMessage(e);
                }
            }
        );
    } else {
        console.warn("⚠️ Variáveis globais não definidas: roomId ou userId");
    }

    if (window.userId) {
        console.log(`🔒 Subscrição privada: dm.${window.userId}`);
        window.Echo.private(`dm.${window.userId}`).listen(
            "DirectMessageSent",
            (e) => {
                if (e.sender_id === window.userId) {
                    console.log("🛑 Ignorado: DM do próprio utilizador");
                    return;
                }

                console.log("📨 Nova DM recebida:", e);

                // ✅ Atualiza visualmente o badge na sidebar
                const contactItem = document.querySelector(
                    `[data-user-id="${e.sender_id}"]`
                );
                if (contactItem) {
                    contactItem.classList.add("has-unread");
                }

                // ✅ Opcional: atualiza a conversa se estiver aberta
                if (typeof window.appendMessage === "function") {
                    window.appendMessage(e);
                }
            }
        );
    }
}, 1000); // espera 1 segundo para garantir que sessão está pronta
