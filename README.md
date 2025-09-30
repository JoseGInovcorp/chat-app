# 💬 Chat App — Sistema de Comunicação Interna

Este projeto tem como objetivo criar um sistema de chat interno, inspirado na interface do [Once Campfire](https://once.com/campfire), com suporte para:

-   Mensagens diretas entre membros da equipa
-   Salas de chat com convites e gestão de participantes
-   Interface moderna e minimalista com autenticação integrada

---

## 🚀 Setup Inicial - 29/09/2025

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

## 🔄 Evolução para Mensagens em Tempo Real - 30/09/2025

### 📡 Broadcasting com Laravel Echo

-   [x] Instalação e configuração do Laravel Echo com Pusher
-   [x] Criação dos eventos `RoomMessageSent` e `DirectMessageSent` com `ShouldBroadcast`
-   [x] Emissão de eventos com `broadcast(...)->toOthers()` para evitar duplicações
-   [x] Autorização de canais privados em `routes/channels.php` com logs de validação
-   [x] Subscrição dinâmica no `bootstrap.js` com `window.roomId` e `window.userId`
-   [x] Listener com filtro para ignorar eventos do próprio utilizador (`sender_id === userId`)
-   [x] Correção crítica: ativação do `php artisan queue:work` para processar eventos
-   [x] Validação visual e funcional com dois utilizadores em paralelo

### 💬 Renderização Dinâmica de Mensagens

-   [x] Função `appendMessage(e)` definida no Blade para inserir mensagens em tempo real
-   [x] Comparação robusta entre `sender_id` e `window.userId` para posicionamento correto
-   [x] Layout flexível: mensagens enviadas à direita (fundo azul), recebidas à esquerda (fundo cinza)
-   [x] Scroll automático para manter a conversa visível
-   [x] Correção da resposta JSON no controller para incluir `sender_id` e evitar renderização incorreta
-   [x] Testes manuais com F5 e sem F5 para garantir consistência

### 🛠️ Arquitetura Técnica Adicional

-   [x] Separação clara entre mensagens de sala (`room_id`) e diretas (`recipient_id`)
-   [x] Eventos com `broadcastWith()` formatado para o frontend
-   [x] Logs no Laravel (`laravel.log`) para cada tentativa de subscrição e emissão
-   [x] Fallback visual com `ui-avatars.com` para utilizadores sem avatar
-   [x] Preparação para agrupamento visual e animações futuras

### 🧪 Testes Realizados

-   [x] Envio e receção de mensagens em tempo real com dois utilizadores
-   [x] Validação visual do layout (direita/esquerda)
-   [x] Verificação de duplicações e correção com `toOthers()`
-   [x] Teste com `queue:work` desligado e ligado
-   [x] Teste com F5 e sem F5 para garantir consistência
-   [x] Teste de permissões para apagar mensagens

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
