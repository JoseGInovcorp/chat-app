import { BadgeManager } from "./utils/badgeManager.js";

// Helpers de formatação
function formatTime(ts) {
    if (!ts) return "";
    const d = new Date(ts);
    return d.toLocaleTimeString("pt-PT", {
        hour: "2-digit",
        minute: "2-digit",
    });
}

function formatDate(ts) {
    if (!ts) return "";
    const d = new Date(ts);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    if (d.toDateString() === today.toDateString()) return "Hoje";
    if (d.toDateString() === yesterday.toDateString()) return "Ontem";
    return d.toLocaleDateString("pt-PT");
}

document.addEventListener("DOMContentLoaded", () => {
    const app = document.getElementById("room-app");
    if (!app) return;

    window.roomId = app.dataset.roomId;
    window.roomSlug = app.dataset.roomSlug;
    window.userId = document.body.dataset.authId || "";

    const messagesDiv = document.getElementById("messages");
    const form = document.getElementById("message-form");
    const input = document.getElementById("message-input");

    BadgeManager.clearBadge("room", window.roomId);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;

    let lastSenderId = null;
    let lastRenderedDate = null;

    window.appendMessage = (msg) => {
        try {
            const m = msg?.message ?? msg;
            if (!m || (!m.id && !m.temp_id)) return;

            const messageKey = m.id ? `message-${m.id}` : `temp-${m.temp_id}`;
            if (messagesDiv.querySelector(`[data-message-id="${messageKey}"]`))
                return;

            // Separador de dia
            const msgDate = formatDate(m.created_at);
            if (lastRenderedDate !== msgDate) {
                const sep = document.createElement("div");
                sep.className = "text-center text-xs text-gray-400 my-2";
                sep.innerText = msgDate;
                messagesDiv.appendChild(sep);
                lastRenderedDate = msgDate;
            }

            const isOwn = String(m.sender_id) === String(window.userId);
            const isSameSender = lastSenderId === String(m.sender_id);

            const div = document.createElement("div");
            div.id = m.id ? `message-${m.id}` : "";
            div.setAttribute("data-message-id", messageKey);
            div.className = `flex flex-col ${
                isOwn ? "items-end" : "items-start"
            } animate-fadeInUp`;

            div.innerHTML = `
                ${
                    !isSameSender
                        ? `
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1 font-semibold">
                    ${m.sender_name ?? ""}
                </div>`
                        : ""
                }
                <div class="max-w-xs px-4 py-2 rounded-xl shadow-sm font-medium ${
                    isOwn
                        ? "bg-blue-500 text-white"
                        : "bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                }">
                    <p class="text-sm whitespace-pre-line">${m.body ?? ""}</p>
                    <span class="text-[10px] opacity-70 block text-right mt-1">
                        ${formatTime(m.created_at)}
                    </span>
                </div>
            `;

            messagesDiv.appendChild(div);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            lastSenderId = String(m.sender_id ?? lastSenderId);

            BadgeManager.clearBadge("room", window.roomId);
        } catch (err) {
            console.warn("appendMessage error (room)", err);
        }
    };

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;

        const tempId = `t${Date.now()}`;
        window.appendMessage({
            temp_id: tempId,
            sender_id: window.userId,
            room_id: window.roomId,
            body,
            sender_name: "Tu",
            created_at: new Date().toISOString(),
        });

        const formData = new FormData(form);
        formData.set("body", body);
        formData.set("temp_id", tempId);

        try {
            const response = await fetch(form.action, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    Accept: "application/json",
                },
                body: formData,
            });

            if (response.ok) {
                const data = await response.json();
                document
                    .querySelector(`[data-message-id="temp-${tempId}"]`)
                    ?.remove();
                window.appendMessage(data);
                input.value = "";

                const token = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                fetch(`/rooms/${window.roomSlug}/read`, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": token,
                        Accept: "application/json",
                    },
                }).catch(() => {});
            }
        } catch (err) {
            console.error("Erro ao enviar mensagem de sala:", err);
        }
    });

    input.addEventListener("keydown", (e) => {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event("submit", { cancelable: true }));
        }
    });

    messagesDiv.addEventListener("click", async (e) => {
        if (e.target.classList.contains("delete-message")) {
            const id = e.target.dataset.id;
            const response = await fetch(`/messages/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    Accept: "application/json",
                },
            });
            if (response.ok) {
                document.getElementById(`message-${id}`)?.remove();
            }
        }
    });

    try {
        if (window.currentRoomChannel) {
            window.Echo.leave(window.currentRoomChannel);
        }
        window.currentRoomChannel = `room.${window.roomId}`;

        window.Echo.private(window.currentRoomChannel).listen(
            "RoomMessageSent",
            (e) => {
                const payload = e?.message ?? e;
                if (String(payload?.room_id) !== String(window.roomId)) return;
                if (String(payload?.sender_id) === String(window.userId))
                    return;

                if (typeof window.appendMessage === "function") {
                    window.appendMessage(payload);
                }

                const token = document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content;
                fetch(`/rooms/${window.roomSlug}/read`, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": token,
                        Accept: "application/json",
                    },
                }).catch(() => {});
            }
        );
    } catch (err) {
        console.error("[echo] erro a subscrever room channel:", err);
    }

    window.addEventListener("beforeunload", () => {
        BadgeManager.clearBadge("room", window.roomId);
        localStorage.removeItem(`roomLastRead:${window.roomId}`);
        if (window.currentRoomChannel) {
            window.Echo.leave(window.currentRoomChannel);
            window.currentRoomChannel = null;
        }
    });
});
