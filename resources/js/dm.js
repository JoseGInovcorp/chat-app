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
    const app = document.getElementById("dm-app");
    if (!app) return; // só corre nesta view

    const peerId = String(parseInt(app.dataset.peerId, 10));
    const authId = String(parseInt(app.dataset.authId, 10));

    // Tornar globais para o handler
    window.peerId = peerId;
    window.userId = authId;

    const win = document.getElementById("dm-window");
    const form = document.getElementById("dm-form");
    const input = document.getElementById("dm-input");

    // ✅ Limpar badge da DM atual ao abrir (visual + storage)
    BadgeManager.clearBadge("dm", peerId);

    // Scroll inicial
    win.scrollTop = win.scrollHeight;

    let lastSenderId = null;
    let lastRenderedDate = null;

    // --- Função para adicionar mensagens dinamicamente ---
    const appendMessage = (msg) => {
        try {
            const m = msg?.message ?? msg;
            if (!m || (!m.id && !m.temp_id)) return;

            const messageKey = m.id ? `message-${m.id}` : `temp-${m.temp_id}`;
            if (win.querySelector(`[data-message-id="${messageKey}"]`)) return;

            // Separador de dia
            const msgDate = formatDate(m.created_at);
            if (lastRenderedDate !== msgDate) {
                const sep = document.createElement("div");
                sep.className = "text-center text-xs text-gray-400 my-2";
                sep.innerText = msgDate;
                win.appendChild(sep);
                lastRenderedDate = msgDate;
            }

            const isOwn = String(m.sender_id) === authId;
            const isSameSender = lastSenderId === String(m.sender_id);

            const div = document.createElement("div");
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

            win.appendChild(div);
            win.scrollTop = win.scrollHeight;
            lastSenderId = String(m.sender_id ?? lastSenderId);

            // ✅ Sempre que mostro mensagem da DM ativa → limpar badge
            BadgeManager.clearBadge("dm", peerId);
        } catch (err) {
            console.warn("appendMessage error (dm)", err);
        }
    };

    window.appendMessage = appendMessage;

    // --- Submissão do formulário ---
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;

        const tempId = `t${Date.now()}`;
        appendMessage({
            temp_id: tempId,
            sender_id: authId,
            recipient_id: peerId,
            body,
            sender_name: "Tu",
            created_at: new Date().toISOString(),
        });

        try {
            const res = await fetch(`/dm/${peerId}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify({ body, temp_id: tempId }),
            });

            if (!res.ok) {
                console.error("Falha ao enviar DM:", res.status);
                return;
            }

            const msg = await res.json();
            const tempEl = win.querySelector(
                `[data-message-id="temp-${tempId}"]`
            );
            if (tempEl) tempEl.remove();
            appendMessage(msg);
            input.value = "";
        } catch (err) {
            console.error("Erro de rede ao enviar DM:", err);
        }
    });

    // --- Atalho Enter para enviar ---
    input.addEventListener("keydown", (e) => {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event("submit", { cancelable: true }));
        }
    });

    // NOTA: o listener Echo para DMs está centralizado em bootstrap.js
});
