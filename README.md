# 💬 Chat App — Sistema de Comunicação Interna

Este projeto tem como objetivo criar um sistema de chat interno, inspirado na interface do [Once Campfire](https://once.com/campfire), com suporte para:

-   Mensagens diretas entre membros da equipa
-   Salas de chat com convites e gestão de participantes
-   Interface moderna e minimalista com autenticação integrada

---

## 🚀 Setup Inicial

-   Laravel instalado com [Herd](https://herd.laravel.com/)
-   Projeto criado em `C:\inovcorp\chat-app`
-   Autenticação pronta com Laravel Breeze (Blade + Tailwind)
-   Base de dados configurada via `.env` e migrada com sucesso

---

## 🧱 Estrutura de Dados

### Utilizadores (`users`)

-   `avatar` (opcional)
-   `name`, `email`, `password`
-   `role`: `admin` ou `user`
-   `status`: `active` ou `inactive`

### Salas (`rooms`)

-   `avatar` (opcional)
-   `name` (único)
-   `slug` (referência única para URL amigável)

### Membros da Sala (`room_user`)

-   `room_id`, `user_id`
-   `invited_by` (quem convidou)
-   `joined_at` (data de entrada)

### Mensagens (`messages`)

-   `sender_id` (quem enviou)
-   `room_id` (mensagem em sala)
-   `recipient_id` (mensagem direta)
-   `body` (conteúdo)
-   `read_at` (quando foi lida)

---

## ✅ Funcionalidades já implementadas

-   [x] Criação do projeto com Laravel Herd
-   [x] Instalação do Laravel Breeze para autenticação
-   [x] Migrations criadas e aplicadas para `rooms`, `room_user`, `messages`
-   [x] Modelos Eloquent definidos com relações entre `User`, `Room`, `Message`
-   [x] Testes manuais via Tinker para validar estrutura e relações
-   [x] **Slugs nas salas** para URLs limpas e únicas
-   [x] **Sidebar fixa** com listagem de Salas e Mensagens Diretas
-   [x] **Mensagens em sala** com envio e remoção via AJAX + auto‑scroll
-   [x] **Mensagens diretas (DMs)** entre utilizadores com a mesma UX das salas
-   [x] **Convites para salas** (apenas admins podem convidar/remover membros)
-   [x] **Policies de permissões** (admin pode apagar qualquer mensagem; user só as suas)
-   [x] **Seeders consistentes** (`ChatSeeder`, `DirectMessagesSeeder`, `ChatDemoSeeder`) com utilizadores, salas e mensagens de exemplo

---

## 👥 Utilizadores de teste

Foram criados automaticamente via seeders:

| Nome  | Email             | Password | Role  |
| ----- | ----------------- | -------- | ----- |
| Admin | admin@example.com | password | admin |
| Alice | alice@example.com | password | user  |
| Bob   | bob@example.com   | password | user  |
| Maria | maria@example.com | password | user  |
| João  | joao@example.com  | password | user  |

---

## 📎 Notas

-   Projeto isolado do sistema de biblioteca para manter domínios separados
-   Interface inspirada no [Once Campfire](https://once.com/campfire) e [Jason Fried](https://x.com/jasonfried/status/1748097864625205586)
-   Repositório mantido na pasta da empresa: `C:\inovcorp\chat-app`

---

## 👨‍💻 Autor

José G. durante estágio na InovCorp.
