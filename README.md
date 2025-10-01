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

---

## 🔁 Melhorias de UX e Correções — 02/10/2025

### 🔔 Badges de Notificação

-   [x] Correção do bug em que o **remetente** também via badge ao enviar mensagem para uma sala (agora apenas os outros membros recebem).
-   [x] Garantia de que badges de DMs e Salas aparecem em tempo real sem necessidade de refresh.
-   [x] Persistência de badges entre navegação e tabs continua assegurada via `localStorage`.

### ⌨️ Envio de Mensagens

-   [x] Unificação da lógica de envio: agora **Enter** envia a mensagem em **todas as views** (salas e DMs).
-   [x] Suporte a **Shift+Enter** para quebra de linha dentro da mesma mensagem.
-   [x] Ajuste aplicado diretamente no `show.blade.php` das salas.

### 🧪 Testes Realizados

-   [x] Alice envia mensagem para Sala Geral → badge aparece no João, **não** na Alice.
-   [x] João envia DM para Alice → badge aparece corretamente no contacto do João na sidebar da Alice.
-   [x] Envio com Enter testado em DMs e Salas → comportamento consistente.
-   [x] Shift+Enter insere nova linha sem enviar.

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

## 🔁 Continuação da Evolução para Mensagens em Tempo Real — 01/10/2025

### 🧭 Sincronização de Notificações e Badges

-   [x] Implementação de lógica `pendingRoomBadges` via `localStorage` para persistência entre views
-   [x] Exposição de helpers públicos `applyPendingRoomBadge()` e `clearPendingRoomBadge()` no layout
-   [x] Dispatch de evento customizado `pendingRoomBadges:updated` para notificar o layout
-   [x] Fallback imediato no `bootstrap.js` para aplicar badge ao receber evento `RoomMessageSent`
-   [ ] Aplicação automática do badge quando o utilizador está numa DM (ainda não ocorre sem refresh)
-   [ ] Observador de mutações (`MutationObserver`) no navigation para aplicar badges quando a sidebar é montada (pronto para integrar)

### 🔐 Autorização e Diagnóstico de Canais Privados

-   [x] Logs detalhados em `routes/channels.php` para cada tentativa de subscrição (`user.{id}`, `room.{id}`)
-   [x] Correção do erro 403 em `/broadcasting/auth` com `withCredentials` e headers CSRF no `Echo`
-   [x] Validação da subscrição ativa via `window.Echo.connector.channels` e estado do socket
-   [x] Testes manuais com `php artisan tinker` para emissão direta de eventos e verificação de receção

### 🧱 Robustez do Bootstrap e Echo

-   [x] Releitura defensiva de variáveis globais (`authId`, `roomId`, `peerId`) com `readGlobals()`
-   [x] Subscrição condicional e atrasada (`setTimeout`) para garantir DOM e sessão estável
-   [x] Fallback visual e funcional para aplicação de badges mesmo sem elementos visíveis
-   [x] Debug hooks no console para inspeção de canais ativos e eventos recebidos

### 🧪 Testes Realizados

-   [x] Envio e receção de mensagens em tempo real com dois utilizadores em views distintas
-   [x] Validação da subscrição aos canais `room.{id}`, `dm.{id}` e `user.{id}`
-   [x] Teste de receção de evento `RoomMessageSent` fora da sala ativa
-   [x] Teste manual de aplicação de badge via `window.applyPendingRoomBadge(id)`
-   [ ] Teste automático de aplicação de badge sem refresh (ainda pendente)
-   [x] Teste de persistência de badges entre tabs e navegação

---

## 📎 Notas

-   Projeto isolado do sistema de biblioteca para manter domínios separados
-   Interface inspirada no [Once Campfire](https://once.com/campfire) e [Jason Fried](https://x.com/jasonfried/status/1748097864625205586)
-   Repositório mantido na pasta da empresa: `C:\inovcorp\chat-app`

---

## 👨‍💻 Autor

José G. durante estágio na InovCorp.
