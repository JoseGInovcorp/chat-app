# Documentação Técnica — Eventos de Broadcast

## `app/Events/DirectMessageSent.php`

**Responsabilidade principal:**  
Evento responsável por transmitir mensagens diretas em tempo real.  
Garante que tanto o remetente como o destinatário recebem a mensagem via canais privados.

**Principais métodos:**

-   `__construct(Message $message)`  
    Carrega a relação `sender` (id, name, avatar) para evitar lazy loading durante o broadcast.
-   `broadcastOn()`  
    Define os canais de broadcast: envia para `user.{recipient_id}` e `user.{sender_id}`.  
    Isto garante sincronização entre remetente e destinatário, inclusive em múltiplas abas.
-   `broadcastWith()`  
    Define os dados enviados no broadcast: id, body, created_at (ISO 8601), sender_id, recipient_id, sender_name, sender_avatar, room_id.  
    A formatação da data é delegada ao frontend.
-   `broadcastAs()`  
    Define o nome do evento como `DirectMessageSent`.

**Eventos emitidos:**

-   `DirectMessageSent` → enviado para os canais `user.{id}`.

**Dependências:**

-   `App\Models\Message`
-   Relação `sender` (User)

**Notas técnicas:**

-   O avatar é resolvido diretamente no evento, mas a boa prática é delegar esta lógica para um accessor no modelo `User`.
-   A inclusão do remetente no broadcast permite sincronização multi-abas.

---

## `app/Events/RoomMessageSent.php`

**Responsabilidade principal:**  
Evento responsável por transmitir mensagens em salas.  
Garante que todos os membros da sala (incluindo o remetente) recebem a mensagem em tempo real.

**Principais métodos:**

-   `__construct(Message $message)`  
    Carrega as relações `sender` e `room.users` para evitar lazy loading durante o broadcast.
-   `broadcastOn()`  
    Define os canais de broadcast:
    -   Canal da sala: `room.{room_id}`
    -   Canal privado de cada membro da sala: `user.{id}`  
        Inclui também o remetente, para sincronização multi-abas.
-   `broadcastWith()`  
    Define os dados enviados no broadcast: id, body, created_at (ISO 8601), sender_id, sender_name, sender_avatar, room_id.  
    A formatação da data é delegada ao frontend.
-   `broadcastAs()`  
    Define o nome do evento como `RoomMessageSent`.

**Eventos emitidos:**

-   `RoomMessageSent` → enviado para `room.{id}` e `user.{id}` de todos os membros.

**Dependências:**

-   `App\Models\Message`
-   Relações `sender` e `room.users`

**Notas técnicas:**

-   A inclusão de todos os membros no broadcast permite que cada utilizador receba notificações mesmo fora da sala.
-   O avatar segue a mesma lógica do evento de DM, com fallback para `ui-avatars.com`.
-   A consistência entre `DirectMessageSent` e `RoomMessageSent` simplifica o tratamento no frontend.

---

# Documentação Técnica — Slugs

## `app/Console/Commands/FillRoomSlugs.php`

**Responsabilidade principal:**  
Comando Artisan para preencher `slug` em salas (`rooms`) que ainda não o tenham definido.

**Assinatura:**

-   `rooms:fill-slugs`

**Descrição:**

-   "Preenche os slugs das salas que ainda não têm slug definido".

**Fluxo principal (`handle()`):**

1. Seleciona todas as salas sem `slug` ou com `slug` vazio.
2. Se não houver → mostra mensagem `"Todas as salas já têm slug."`.
3. Para cada sala:
    - Gera slug com `Str::slug($room->name)` + sufixo aleatório (`Str::random(6)`).
    - Guarda no modelo.
    - Mostra linha `"Slug criado para sala [id] → slug"`.
4. No fim → mostra `"Slugs preenchidos com sucesso!"`.

**Dependências:**

-   `App\Models\Room`
-   `Illuminate\Support\Str`
-   `Illuminate\Console\Command`

**Notas técnicas:**

-   Útil após migração que adiciona coluna `slug` a `rooms`.
-   Garante unicidade ao concatenar string aleatória.
-   Pode ser executado via `php artisan rooms:fill-slugs`.

---

# Documentação Técnica — Controllers

## `app/Http/Controllers/DirectMessageController.php`

**Responsabilidade principal:**  
Gerir mensagens diretas (DMs).  
Permite listar contactos, visualizar conversas, enviar novas mensagens e marcar mensagens como lidas.

**Principais métodos:**

-   `index()`  
    Lista todos os contactos disponíveis (todos os utilizadores exceto o autenticado).  
    Retorna a view `dm.index`.

-   `show(User $user)`  
    Mostra a conversa entre o utilizador autenticado e o `$user`.

    -   Impede que o utilizador abra conversa consigo próprio.
    -   Marca mensagens como lidas (`Message::markAsReadFrom`).
    -   Carrega as últimas 50 mensagens, ordenadas por data.  
        Retorna a view `dm.show`.

-   `store(Request $request, User $user)`  
    Cria uma nova mensagem direta:

    -   Valida o corpo da mensagem (`body`).
    -   Impede envio para si próprio.
    -   Cria a mensagem e dispara o evento `DirectMessageSent` para broadcast em tempo real.
    -   Retorna JSON (para AJAX) ou redireciona de volta com sucesso.

-   `markActiveRead(User $user)`  
    Marca como lidas todas as mensagens recebidas de `$user` quando a thread está ativa.  
    Retorna JSON de confirmação.

**Eventos emitidos:**

-   `DirectMessageSent` → broadcast em tempo real para remetente e destinatário.

**Dependências:**

-   `App\Models\Message`
-   `App\Models\User`
-   `App\Events\DirectMessageSent`

**Notas técnicas:**

-   O método `store` retorna JSON formatado para consumo direto no frontend (id, body, created_at ISO 8601, sender info).
-   O método `markActiveRead` é usado pelo frontend para sincronizar estado de leitura.
-   A lógica de avatar poderia ser delegada para um accessor no modelo `User`.

---

## `app/Http/Controllers/MessageController.php`

**Responsabilidade principal:**  
Gerir mensagens em geral (tanto de salas como diretas).  
Permite criar novas mensagens e apagar mensagens existentes.

**Principais métodos:**

-   `store(Request $request)`  
    Cria uma nova mensagem:

    -   Valida `body`, `room_id` (opcional) e `recipient_id` (opcional).
    -   Garante que pelo menos um destino é indicado (sala ou utilizador).
    -   Cria a mensagem e carrega o `sender`.
    -   Se for mensagem de sala → dispara `RoomMessageSent`.
    -   Se for mensagem direta → dispara `DirectMessageSent`.
    -   Retorna JSON (para AJAX) ou redireciona para a sala.

-   `destroy(Message $message)`  
    Apaga uma mensagem:
    -   Apenas o autor ou um admin pode apagar.
    -   Remove a mensagem da base de dados.
    -   Retorna JSON (para AJAX) ou redireciona para a sala.

**Eventos emitidos:**

-   `RoomMessageSent` → broadcast em tempo real para todos os membros da sala.
-   `DirectMessageSent` → broadcast em tempo real para remetente e destinatário.

**Dependências:**

-   `App\Models\Message`
-   `App\Events\RoomMessageSent`
-   `App\Events\DirectMessageSent`

**Notas técnicas:**

-   O método `store` centraliza a criação de mensagens, independentemente do tipo (sala ou DM).
-   O método `destroy` aplica regras de autorização simples (autor ou admin).
-   A resposta JSON inclui dados prontos para renderização no frontend (id, body, created_at ISO 8601, sender info, room_id, recipient_id).
-   A lógica de avatar também poderia ser delegada para um accessor no modelo `User`.

---

## `app/Http/Controllers/ProfileController.php`

**Responsabilidade principal:**  
Gerir o perfil do utilizador autenticado.  
Permite visualizar, atualizar e eliminar a conta.

**Principais métodos:**

-   `edit(Request $request): View`  
    Retorna a view `profile.edit` com os dados do utilizador autenticado.

-   `update(ProfileUpdateRequest $request): RedirectResponse`  
    Atualiza os dados do utilizador:

    -   Valida os campos através de `ProfileUpdateRequest`.
    -   Se o email for alterado, remove a verificação (`email_verified_at = null`).
    -   Suporta resposta JSON (status + dados básicos do utilizador) ou redirecionamento com mensagem de sucesso.

-   `destroy(Request $request): RedirectResponse`  
    Elimina a conta do utilizador autenticado:
    -   Valida a password atual.
    -   Faz logout (`Auth::logout()`).
    -   Elimina o utilizador.
    -   Invalida a sessão e regenera o token CSRF.
    -   Redireciona para `/`.

**Dependências:**

-   `App\Http\Requests\ProfileUpdateRequest`
-   `Illuminate\Support\Facades\Auth`
-   `Illuminate\Support\Facades\Redirect`

**Notas técnicas:**

-   Sugere-se disparar um evento `UserDeleted` ou registar log de auditoria no `destroy`.
-   O método `update` suporta tanto fluxo web como API (JSON).

---

## `app/Http/Controllers/RoomController.php`

**Responsabilidade principal:**  
Gerir salas de chat.  
Permite listar, visualizar, criar, convidar utilizadores e marcar mensagens como lidas.

**Principais métodos:**

-   `index()`  
    Lista todas as salas.  
    Retorna a view `rooms.index`.

-   `show(Room $room)`  
    Mostra uma sala específica:

    -   Verifica autorização (`view`).
    -   Se o utilizador não for membro, adiciona-o à sala.
    -   Atualiza `last_read_at` no pivot da relação `room_user`.
    -   Carrega utilizadores da sala.
    -   Carrega todas as mensagens da sala (com `sender`).  
        Retorna a view `rooms.show`.

-   `create()`  
    Verifica autorização (`create`) e retorna a view `rooms.create`.

-   `store(Request $request)`  
    Cria uma nova sala:

    -   Valida `name` e `avatar`.
    -   Cria a sala com `slug` único (`Str::slug + random`).
    -   Adiciona o utilizador autenticado como membro inicial.
    -   Redireciona para a sala criada.

-   `inviteForm(Room $room)`  
    Verifica autorização (`invite`).  
    Lista todos os utilizadores (exceto o autenticado).  
    Retorna a view `rooms.invite`.

-   `invite(Request $request, Room $room)`  
    Verifica autorização (`invite`).  
    Valida `user_id`.  
    Adiciona o utilizador à sala com `joined_at` e `last_read_at`.  
    Retorna com mensagem de sucesso.

-   `markActiveRead(Room $room)`  
    Atualiza `last_read_at` do utilizador autenticado no pivot da sala.  
    Retorna JSON de confirmação.

**Dependências:**

-   `App\Models\Room`
-   `App\Models\User`
-   `Illuminate\Support\Str`

**Notas técnicas:**

-   O método `show` usa `get()` em vez de `paginate()` para carregar todas as mensagens.
-   O pivot `room_user` guarda informações adicionais: `invited_by`, `joined_at`, `last_read_at`.
-   O método `markActiveRead` é usado pelo frontend para sincronizar estado de leitura em tempo real.

---

# Documentação Técnica — Models

## `app/Models/Message.php`

**Responsabilidade principal:**  
Representa mensagens enviadas no sistema (tanto diretas como em salas).  
Inclui validações, relações e métodos utilitários para leitura/não lidas.

**Atributos principais:**

-   `sender_id` → utilizador que enviou a mensagem.
-   `recipient_id` → destinatário (para DMs).
-   `room_id` → sala (para mensagens em grupo).
-   `body` → conteúdo da mensagem.
-   `read_at` → timestamp de leitura.

**Validações:**

-   Não pode ter `room_id` e `recipient_id` ao mesmo tempo (garantido no `booted()`).

**Relações:**

-   `sender()` → pertence a `User` (remetente).
-   `recipient()` → pertence a `User` (destinatário).
-   `room()` → pertence a `Room`.

**Scopes e métodos utilitários:**

-   `scopeDirectBetween($query, $userA, $userB)` → filtra mensagens diretas entre dois utilizadores.
-   `scopeUnread($query)` → filtra mensagens não lidas (`read_at IS NULL`).
-   `unreadFrom(User $sender, User $recipient)` → devolve contagem de mensagens não lidas de `$sender` para `$recipient`.
-   `markAsReadFrom(User $sender, User $recipient)` → marca como lidas todas as mensagens de `$sender` para `$recipient`.

**Notas técnicas:**

-   `created_at` e `read_at` são tratados como `datetime`.
-   A validação no `booted()` evita inconsistências entre DMs e salas.

---

## `app/Models/Room.php`

**Responsabilidade principal:**  
Representa salas de chat.  
Inclui relações com utilizadores e mensagens, e lógica de contagem de mensagens não lidas.

**Atributos principais:**

-   `name` → nome da sala.
-   `avatar` → imagem associada.
-   `slug` → identificador único usado em rotas.

**Relações:**

-   `users()` → relação N:N com `User`, incluindo atributos pivot:
    -   `invited_by`
    -   `joined_at`
    -   `last_read_at`
-   `messages()` → relação 1:N com `Message`, ordenadas por `created_at`.

**Métodos utilitários:**

-   `getRouteKeyName()` → usa `slug` em vez de `id` nas rotas.
-   `unreadCountFor(User $user)` → devolve número de mensagens não lidas para um utilizador, comparando `created_at` das mensagens com `last_read_at` no pivot.

**Notas técnicas:**

-   O pivot `room_user` funciona como tabela de associação com metadados.
-   O método `unreadCountFor` ignora mensagens enviadas pelo próprio utilizador.

---

## `app/Models/User.php`

**Responsabilidade principal:**  
Representa utilizadores autenticados.  
Inclui autenticação, notificações e relações com salas e mensagens.

**Atributos principais:**

-   `name`, `email`, `password`, `avatar`, `role`, `status`, `is_admin`.
-   `password` e `remember_token` estão ocultos (`hidden`).
-   `is_admin` é convertido para boolean.
-   `password` é armazenado como hash.

**Relações:**

-   `rooms()` → relação N:N com `Room`, incluindo `invited_by` e `joined_at`.
-   `sentMessages()` → relação 1:N com `Message` (mensagens enviadas).
-   `receivedMessages()` → relação 1:N com `Message` (mensagens recebidas).

**Helpers:**

-   `isAdmin()` → devolve `true` se o utilizador for admin (`is_admin` ou `role === 'admin'`).
-   `getAvatarUrlAttribute()` → devolve URL do avatar ou fallback para `ui-avatars.com`.

**Notas técnicas:**

-   Usa `HasFactory` para integração com factories.
-   Usa `Notifiable` para notificações.
-   O accessor `avatar_url` simplifica a lógica de exibição no frontend.

---

# Documentação Técnica — Policies

## `app/Policies/RoomPolicy.php`

**Responsabilidade principal:**  
Gerir permissões relacionadas a salas de chat.  
Define quem pode criar, convidar, visualizar, atualizar e eliminar salas.

**Principais métodos:**

-   `create(User $user): bool`  
    Apenas administradores podem criar novas salas.

-   `invite(User $user, Room $room): bool`  
    Apenas administradores ou membros da sala podem convidar outros utilizadores.

-   `view(User $user, Room $room): bool`  
    Um utilizador pode visualizar a sala se for administrador ou membro.

-   `update(User $user, Room $room): bool`  
    Apenas administradores ou o utilizador que criou a sala (via `invited_by` no pivot) podem atualizar.

-   `delete(User $user, Room $room): bool`  
    Apenas administradores podem eliminar salas.

**Dependências:**

-   `App\Models\Room`
-   `App\Models\User`

**Notas técnicas:**

-   A verificação de pertença à sala é feita via relação `users()` no modelo `Room`.
-   O campo `invited_by` no pivot é usado para identificar o criador da sala.
-   Esta policy é usada em conjunto com `authorize()` nos controllers (`RoomController`).
-   Centraliza a lógica de permissões, garantindo consistência em toda a aplicação.

---

# Documentação Técnica — Providers

## `app/Providers/AppServiceProvider.php`

**Responsabilidade principal:**  
Service Provider principal da aplicação.  
Regista _view composers_ e bindings globais.

**Principais métodos:**

-   `register()`  
    Método reservado para bindings de serviços. (Atualmente vazio).
-   `boot()`  
    Define um _view composer_ para `layouts.navigation`:
    -   Carrega as salas do utilizador autenticado, incluindo `last_read_at` no pivot.
    -   Calcula `unread_count` para cada sala via `Room::unreadCountFor()`.
    -   Carrega contactos diretos ativos (exceto o próprio utilizador).
    -   Calcula `unread_count` para cada contacto via `Message::unreadFrom()`.
    -   Injeta `rooms` e `directContacts` na view.

**Dependências:**

-   `App\Models\User`
-   `App\Models\Message`
-   `Illuminate\Support\Facades\View`

**Notas técnicas:**

-   Boa prática: mover este _view composer_ para um `ViewServiceProvider` dedicado.
-   O cálculo de não lidas é feito no servidor, simplificando a renderização inicial da navegação.

---

## `app/Providers/AuthServiceProvider.php`

**Responsabilidade principal:**  
Registar _policies_ e _gates_ de autorização.

**Principais métodos:**

-   `boot()`
    -   Regista as policies definidas em `$policies`.
    -   Exemplo comentado de `Gate::define` para permissões adicionais.

**Policies registadas:**

-   `Room::class` → `RoomPolicy::class`

**Dependências:**

-   `App\Models\Room`
-   `App\Policies\RoomPolicy`
-   `Illuminate\Support\Facades\Gate`

**Notas técnicas:**

-   Centraliza a ligação entre modelos e policies.
-   Pode ser expandido com _gates_ adicionais para permissões específicas.

---

## `app/Providers/BroadcastServiceProvider.php`

**Responsabilidade principal:**  
Configurar rotas e canais de broadcast.  
Necessário para eventos em tempo real (Laravel Echo, Pusher, Soketi).

**Principais métodos:**

-   `boot()`
    -   Regista rotas de autenticação de broadcast (`/broadcasting/auth`) com middleware `web` e `auth`.
    -   Carrega definições de canais privados/presence a partir de `routes/channels.php`.

**Dependências:**

-   `Illuminate\Support\Facades\Broadcast`

**Notas técnicas:**

-   Essencial para que eventos `ShouldBroadcast` funcionem corretamente.
-   Os canais privados são definidos em `routes/channels.php`.

---

## `app/Providers/RouteServiceProvider.php`

**Responsabilidade principal:**  
Gerir grupos de rotas da aplicação.  
Inclui configuração de _rate limiting_ e _route model binding_.

**Principais métodos:**

-   `boot()`
    -   Define _rate limiting_ para APIs: 60 requisições por minuto, por utilizador ou IP.
    -   Define _route model binding_: parâmetro `room` resolve para `App\Models\Room` via `slug`.
    -   Regista grupos de rotas:
        -   `web.php` com middleware `web`.
        -   `api.php` com middleware `api` e prefixo `api`.
        -   (Comentado) exemplo de rotas admin.

**Dependências:**

-   `App\Models\Room`
-   `Illuminate\Support\Facades\Route`
-   `Illuminate\Support\Facades\RateLimiter`
-   `Illuminate\Cache\RateLimiting\Limit`

**Notas técnicas:**

-   O uso de `slug` em vez de `id` para `Room` melhora URLs amigáveis.
-   O _rate limiting_ protege endpoints de API contra abuso.
-   Estrutura preparada para expansão com rotas admin dedicadas.

---

# Documentação Técnica — Base de Dados

## `database/factories/UserFactory.php`

**Responsabilidade principal:**  
Gerar utilizadores falsos para testes e seeders.

**Estados e métodos:**

-   `definition()`

    -   Gera nome e email únicos.
    -   Define `email_verified_at` como `now()`.
    -   Define `password` com hash (usa `DEFAULT_FACTORY_PASSWORD` do `.env` ou `"password"`).
    -   Gera `remember_token`.

-   `unverified()`

    -   Define `email_verified_at = null`.

-   `admin()`

    -   Define `role = 'admin'`.

-   `inactive()`
    -   Define `status = 'inactive'`.

**Notas técnicas:**

-   Usa `Hash::make` para password.
-   Permite criar utilizadores com diferentes estados para testes (admin, inativo, etc.).

---

## `database/migrations/xxxx_xx_xx_create_rooms_table.php`

**Responsabilidade principal:**  
Criar tabela `rooms`.

**Campos principais:**

-   `id`
-   `avatar` (nullable)
-   `name` (único)
-   `slug` (único, identificador amigável)
-   `description` (nullable)
-   `created_by` (FK para `users`, cascade on delete)
-   Timestamps (`created_at`, `updated_at`)

**Notas técnicas:**

-   `slug` garante URLs amigáveis.
-   `created_by` mantém referência ao criador da sala.

---

## `database/migrations/xxxx_xx_xx_create_messages_table.php`

**Responsabilidade principal:**  
Criar tabela `messages`.

**Campos principais:**

-   `id`
-   `sender_id` (FK para `users`, cascade on delete)
-   `room_id` (nullable, FK para `rooms`, cascade on delete)
-   `recipient_id` (nullable, FK para `users`, cascade on delete)
-   `body` (conteúdo da mensagem)
-   `type` (default `text`)
-   `read_at` (nullable)
-   Timestamps (`created_at`, `updated_at`)
-   Soft deletes (`deleted_at`)

**Índices:**

-   `room_id, recipient_id`
-   `sender_id`
-   `created_at`

**Notas técnicas:**

-   Suporta mensagens de sala **ou** diretas (não ambas).
-   `softDeletes` permite histórico de mensagens apagadas.
-   Comentário sugere constraint check para garantir que pelo menos `room_id` ou `recipient_id` está definido.

---

## `database/migrations/xxxx_xx_xx_create_room_user_table.php`

**Responsabilidade principal:**  
Criar tabela pivot `room_user` (ligação N:N entre `rooms` e `users`).

**Campos principais:**

-   `id`
-   `room_id` (FK, cascade on delete)
-   `user_id` (FK, cascade on delete)
-   `invited_by` (nullable, FK para `users`, null on delete)
-   `joined_at` (nullable)
-   `role` (default `member`)
-   `status` (default `active`)
-   Timestamps (`created_at`, `updated_at`)
-   Soft deletes (`deleted_at`)

**Constraints e índices:**

-   `unique(room_id, user_id)` → garante que um utilizador só pode estar uma vez em cada sala.
-   Índice em `invited_by`.

**Notas técnicas:**

-   Permite guardar metadados da relação (quem convidou, quando entrou, estado).
-   `softDeletes` mantém histórico de membros removidos.

---

## `database/migrations/xxxx_xx_xx_add_role_and_status_to_users_table.php`

**Responsabilidade principal:**  
Adicionar colunas extra à tabela `users`.

**Campos adicionados:**

-   `avatar` (nullable)
-   `role` (default `user`, indexado)
-   `status` (default `active`, indexado)

**Notas técnicas:**

-   Uso de `string` em vez de `enum` → mais flexível para evoluções futuras.
-   `role` e `status` indexados para facilitar queries.

---

## `database/migrations/xxxx_xx_xx_add_slug_to_rooms_table.php`

**Responsabilidade principal:**  
Adicionar coluna `slug` à tabela `rooms`.

**Campos adicionados:**

-   `slug` (string, único, obrigatório, após `name`)

**Notas técnicas:**

-   Garante URLs amigáveis e únicas para cada sala.
-   Pode coexistir com a migration inicial de `rooms` (dependendo da ordem de execução).

---

## `database/migrations/xxxx_xx_xx_create_direct_messages_table.php`

**Responsabilidade principal:**  
Criar tabela `direct_messages` para armazenar mensagens diretas (DMs) entre utilizadores.

**Campos principais:**

-   `id`
-   `sender_id` (FK para `users`, cascade on delete)
-   `receiver_id` (FK para `users`, cascade on delete)
-   `content` (texto da mensagem)
-   `type` (default `text`, permite extensibilidade para outros tipos de conteúdo)
-   `read_at` (nullable, marca quando a mensagem foi lida)
-   Timestamps (`created_at`, `updated_at`)
-   Soft deletes (`deleted_at`)

**Índices:**

-   Composto em `sender_id, receiver_id` (para consultas rápidas entre pares).
-   `created_at` (para ordenação cronológica).

**Notas técnicas:**

-   Estrutura semelhante à tabela `messages`, mas dedicada a DMs.
-   `softDeletes` permite manter histórico de mensagens apagadas.
-   Pode coexistir com `messages` dependendo da arquitetura (separação clara entre DMs e salas).

---

## `database/migrations/xxxx_xx_xx_add_read_at_to_messages_table.php`

**Responsabilidade principal:**  
Adicionar coluna `read_at` à tabela `messages`.

**Campos adicionados:**

-   `read_at` (timestamp, nullable) → indica quando a mensagem foi lida.

**Índices:**

-   Índice em `read_at` para melhorar performance em queries de mensagens não lidas.

**Notas técnicas:**

-   Permite unificar a lógica de leitura entre mensagens de sala e diretas.
-   Pode sobrepor-se à lógica já existente em `direct_messages` (dependendo da evolução do schema).

---

## `database/migrations/xxxx_xx_xx_add_is_admin_to_users_table.php`

**Responsabilidade principal:**  
Adicionar coluna `is_admin` à tabela `users`.

**Campos adicionados:**

-   `is_admin` (boolean, default `false`) → indica se o utilizador é administrador.

**Notas técnicas:**

-   Redundante se já existir a coluna `role`.
-   Útil como _flag_ rápida para verificações de permissões.
-   Mantém compatibilidade com `User::isAdmin()` no modelo.

---

# Documentação Técnica — Frontend Handlers

## `resources/js/handlers/dmHandler.js`

**Responsabilidade principal:**  
Gerir eventos de mensagens diretas (`DirectMessageSent`) recebidos via Echo.  
Decide se deve renderizar a mensagem imediatamente, limpar badges ou aplicar badge de notificação.

**Fluxo principal:**

1. Extrai `sender_id`, `recipient_id` e `userId` autenticado.
2. Ignora mensagens enviadas pelo próprio utilizador.
3. Determina o `peerId` (o outro utilizador da conversa).
4. Verifica se a thread ativa corresponde ao `peerId`.
    - **Se sim:**
        - Limpa badge local (`BadgeManager.clearBadge`).
        - Renderiza a mensagem (`window.appendMessage`).
        - Se o utilizador for o destinatário, envia `POST /dm/{sender}/read` para marcar como lida no servidor.
    - **Se não:**
        - Aplica badge local (`BadgeManager.applyBadge`).

**Dependências:**

-   `BadgeManager` (gestão de badges).
-   `window.appendMessage` (renderização de mensagens).
-   `fetch` para marcar mensagens como lidas no servidor.
-   `meta[name="csrf-token"]` para autenticação CSRF.

**Notas técnicas:**

-   O handler garante sincronização entre cliente e servidor no estado de leitura.
-   Evita duplicação de mensagens próprias (`if (me === sender) return`).
-   Mantém consistência de badges entre threads ativas e inativas.

---

## `resources/js/handlers/roomHandler.js`

**Responsabilidade principal:**  
Gerir eventos de mensagens de sala (`RoomMessageSent`) recebidos via Echo.  
Responsável apenas por aplicar ou limpar badges de notificação.

**Fluxo principal:**

1. Extrai `room_id`, `sender_id` e `created_at` da mensagem.
2. Ignora mensagens enviadas pelo próprio utilizador.
3. Compara `created_at` da mensagem com `lastRead` guardado em `localStorage`.
    - Se a mensagem for anterior ou igual ao `lastRead`, ignora.
4. Verifica se a sala atual (`window.roomId`) é a mesma da mensagem.
    - **Se sim:** limpa badge (`BadgeManager.clearBadge`).
    - **Se não:** aplica badge (`BadgeManager.applyBadge`).

**Dependências:**

-   `BadgeManager` (gestão de badges).
-   `localStorage` (`roomLastRead:{roomId}`) para estado de leitura.
-   `window.roomId` para identificar sala ativa.

**Notas técnicas:**

-   Mantém consistência de notificações entre múltiplas abas.
-   Garante que apenas mensagens posteriores ao `lastRead` disparam badges.
-   Não renderiza mensagens — apenas gere badges (renderização é feita em `room.js`).

---

# Documentação Técnica — Frontend Utils

## `resources/js/utils/badgeManager.js`

**Responsabilidade principal:**  
Gerir badges de notificações (tanto de DMs como de salas).  
Mantém consistência entre abas, aplica/limpa badges no DOM e sincroniza estado via `localStorage`.

**Funções internas:**

-   `updateStorage(key, id, action = "add")`  
    Atualiza listas persistidas em `localStorage` (`pendingBadges` para DMs, `pendingRoomBadges` para salas).
    -   `action = "add"` → adiciona id à lista.
    -   `action = "remove"` → remove id da lista.

**API pública (`BadgeManager`):**

-   `applyBadge(type, id)`

    -   Mostra badge no DOM (`[data-badge-type][data-badge-id]`).
    -   Atualiza `localStorage`.
    -   Evita aplicar badge se a thread está ativa (`window.peerId` ou `window.roomId`).
    -   Para salas, evita aplicar badge se `lastRead` for mais recente que a mensagem.

-   `clearBadge(type, id)`

    -   Esconde badge no DOM.
    -   Remove id da lista em `localStorage`.

-   `applyAll()`

    -   Reaplica todos os badges persistidos em `localStorage`.
    -   Ignora badges da thread ativa (remove-os de `localStorage`).
    -   Útil no carregamento inicial da aplicação.

-   `syncStorage(e)`
    -   Listener para evento `storage`.
    -   Mantém sincronização de badges entre múltiplas abas.
    -   Reaplica badges no DOM com base em `e.newValue`.

**Dependências:**

-   `localStorage` (persistência de estado).
-   DOM (`data-badge-type`, `data-badge-id`).
-   `window.peerId` e `window.roomId` (para identificar thread ativa).

**Notas técnicas:**

-   Garante consistência de notificações mesmo em múltiplas abas.
-   Usa `Set` para evitar duplicação de IDs.
-   Estrutura preparada para suportar múltiplos tipos de badge (`dm`, `room`).

---

## `resources/js/app.js`

**Responsabilidade principal:**  
Ficheiro de entrada principal do frontend.  
Carrega dependências globais, inicializa Echo/Pusher, regista handlers e aplica badges persistidos.

**Principais responsabilidades esperadas:**

-   Importar e inicializar `bootstrap.js` (Echo, Axios, etc.).
-   Importar `BadgeManager` e aplicar `applyAll()` no carregamento inicial.
-   Registar listener `window.addEventListener("storage", BadgeManager.syncStorage)` para sincronização entre abas.
-   Importar handlers (`dmHandler.js`, `roomHandler.js`) e associá-los a eventos Echo (`DirectMessageSent`, `RoomMessageSent`).
-   Garantir que badges e mensagens em tempo real funcionam de forma integrada.

**Dependências:**

-   `bootstrap.js` (configuração global de Echo e Axios).
-   `BadgeManager` (gestão de badges).
-   `dmHandler.js` e `roomHandler.js` (gestão de eventos em tempo real).

**Notas técnicas:**

-   Serve como ponto de entrada único para o bundle JS.
-   Centraliza inicialização de funcionalidades globais.
-   Mantém consistência entre backend (Laravel Events) e frontend (Echo + Handlers).

---

# Documentação Técnica — Frontend Core

## `resources/js/app.js`

**Responsabilidade principal:**  
Ficheiro de entrada principal do frontend.  
Carrega dependências globais e inicializa Alpine.js.

**Fluxo principal:**

-   Importa `./bootstrap` (configuração global de Echo, Axios, etc.).
-   Importa Alpine.js e expõe em `window.Alpine`.
-   Inicia Alpine (`Alpine.start()`).

**Dependências:**

-   `bootstrap.js`
-   `alpinejs`

**Notas técnicas:**

-   Serve como ponto de entrada único para o bundle JS.
-   Mantém Alpine disponível globalmente para componentes Blade.

---

## `resources/js/bootstrap.js`

**Responsabilidade principal:**  
Configuração global de Echo, Pusher e Axios.  
Regista listeners para eventos em tempo real (`DirectMessageSent`, `RoomMessageSent`).

**Fluxo principal:**

1. **Configuração Axios**

    - Define headers padrão (`X-Requested-With`, `X-CSRF-TOKEN`).
    - Ativa `withCredentials`.

2. **Configuração Echo/Pusher**

    - Inicializa `window.Echo` com Pusher.
    - Usa variáveis de ambiente (`VITE_PUSHER_APP_KEY`, `VITE_PUSHER_APP_CLUSTER`).
    - Força TLS.

3. **Função `readGlobals()`**

    - Lê variáveis globais do DOM (`authId`, `roomId`, `peerId`).
    - Define `window.userId`, `window.roomId`, `window.peerId`.

4. **Função `initListenersOnce()`**

    - Garante execução única.
    - Se existir `window.userId`, subscreve canal privado `user.{id}`.
    - Regista listeners para:
        - `DirectMessageSent` (várias variantes de namespace).
        - `RoomMessageSent` (várias variantes de namespace).
    - Marca `initListenersOnce.done = true`.

5. **Bootstrap**
    - Executa `initListenersOnce()` no `DOMContentLoaded`.

**Dependências:**

-   `laravel-echo`
-   `pusher-js`
-   `axios`
-   `dmHandler.js`
-   `roomHandler.js`

**Notas técnicas:**

-   Usa múltiplas variantes de namespace para compatibilidade (`DirectMessageSent`, `.App\\Events\\DirectMessageSent`, `.App.Events.DirectMessageSent`).
-   `initListenersOnce` evita registos duplicados.
-   `readGlobals` garante que os handlers sabem qual é a thread ativa.

---

## `resources/js/dm.js`

**Responsabilidade principal:**  
Gerir a interface de mensagens diretas (DMs).  
Renderiza mensagens, envia novas mensagens e integra badges.

**Fluxo principal:**

1. **Helpers de formatação**

    - `formatTime(ts)` → devolve hora `HH:mm`.
    - `formatDate(ts)` → devolve `Hoje`, `Ontem` ou `dd/mm/yyyy`.

2. **Inicialização (`DOMContentLoaded`)**

    - Verifica se existe `#dm-app`.
    - Define `window.peerId` e `window.userId`.
    - Seleciona elementos (`dm-window`, `dm-form`, `dm-input`).
    - Limpa badge da DM ativa (`BadgeManager.clearBadge`).
    - Faz scroll inicial para o fundo.

3. **Função `appendMessage(msg)`**

    - Evita duplicados (`data-message-id`).
    - Insere separador de dia quando muda a data.
    - Renderiza mensagem com estilo diferente para remetente/destinatário.
    - Formata hora com `formatTime`.
    - Faz scroll automático para o fundo.
    - Atualiza `lastSenderId`.
    - Limpa badge da DM ativa.

4. **Envio de mensagens (`form.submit`)**

    - Cria mensagem temporária (`temp_id`).
    - Renderiza imediatamente no DOM.
    - Envia via `fetch` (`POST /dm/{peerId}`).
    - Substitui mensagem temporária pela resposta do servidor.
    - Limpa input.

5. **Atalho Enter**
    - Envia mensagem ao pressionar Enter (sem Shift).

**Dependências:**

-   `BadgeManager`
-   `window.appendMessage` (exposto globalmente)
-   `fetch` para envio de mensagens
-   `meta[name="csrf-token"]` para CSRF

**Notas técnicas:**

-   O listener Echo para DMs está centralizado em `bootstrap.js`.
-   `appendMessage` insere separadores de dia retroativos apenas para novas mensagens.
-   Estrutura preparada para mensagens temporárias (`temp_id`) até confirmação do servidor.

---

## `resources/js/navigation.js`

**Responsabilidade principal:**  
Gerir a navegação da aplicação (lista de DMs e salas) e sincronizar badges entre abas.

**Fluxo principal:**

1. **Reaplicar badges no arranque**

    - `BadgeManager.applyAll()` → reaplica badges persistidos em `localStorage`.

2. **Cross-tab sync**

    - Listener `storage` → chama `BadgeManager.syncStorage` para sincronizar badges entre múltiplas abas.

3. **Delegação de cliques**

    - Clique em `.direct-contact`:
        - Limpa badge da DM clicada.
        - Atualiza globais (`window.peerId`, `window.roomId = null`).
    - Clique em `.room-link`:
        - Limpa badge da sala clicada.
        - Atualiza globais (`window.roomId`, `window.peerId = null`).

4. **Echo listeners → eventos custom**

    - `pendingBadges:updated` → aplica badge de DM se não for a thread ativa.
    - `pendingRoomBadges:updated` → aplica badge de sala se não for a sala ativa.

5. **Exposição global**
    - `window.BadgeManager = BadgeManager` (opcional, para debug ou uso global).

**Dependências:**

-   `BadgeManager`
-   DOM (`.direct-contact`, `.room-link`)
-   `localStorage` (para sincronização de badges)

**Notas técnicas:**

-   Garante consistência de badges entre múltiplas abas.
-   Usa delegação de eventos para capturar cliques em elementos dinâmicos.
-   Eventos custom (`pendingBadges:updated`, `pendingRoomBadges:updated`) permitem integração com outros módulos.

---

## `resources/js/room.js`

**Responsabilidade principal:**  
Gerir a interface de mensagens em salas.  
Renderiza mensagens, envia novas mensagens, integra badges e subscreve canal Echo da sala.

**Fluxo principal:**

1. **Helpers de formatação**

    - `formatTime(ts)` → devolve hora `HH:mm`.
    - `formatDate(ts)` → devolve `Hoje`, `Ontem` ou `dd/mm/yyyy`.

2. **Inicialização (`DOMContentLoaded`)**

    - Verifica se existe `#room-app`.
    - Define `window.roomId`, `window.roomSlug`, `window.userId`.
    - Seleciona elementos (`messages`, `message-form`, `message-input`).
    - Limpa badge da sala ativa (`BadgeManager.clearBadge`).
    - Faz scroll inicial para o fundo.

3. **Função `appendMessage(msg)`**

    - Evita duplicados (`data-message-id`).
    - Insere separador de dia quando muda a data.
    - Renderiza mensagem com estilo diferente para remetente/destinatário.
    - Formata hora com `formatTime`.
    - Faz scroll automático para o fundo.
    - Atualiza `lastSenderId`.
    - Limpa badge da sala ativa.

4. **Envio de mensagens (`form.submit`)**

    - Cria mensagem temporária (`temp_id`).
    - Renderiza imediatamente no DOM.
    - Envia via `fetch` (`POST form.action`).
    - Substitui mensagem temporária pela resposta do servidor.
    - Limpa input.
    - Marca sala como lida (`POST /rooms/{slug}/read`).

5. **Atalho Enter**

    - Envia mensagem ao pressionar Enter (sem Shift).

6. **Eliminar mensagens (`click .delete-message`)**

    - Envia `DELETE /messages/{id}`.
    - Remove mensagem do DOM se sucesso.

7. **Subscrição Echo**

    - Sai de canal anterior se existir (`Echo.leave`).
    - Subscreve `room.{roomId}`.
    - Listener `RoomMessageSent`:
        - Ignora mensagens da própria pessoa.
        - Renderiza mensagem recebida.
        - Marca sala como lida (`POST /rooms/{slug}/read`).

8. **Before unload**
    - Limpa badge da sala ativa.
    - Remove `roomLastRead:{roomId}` de `localStorage`.
    - Sai do canal Echo da sala.

**Dependências:**

-   `BadgeManager`
-   `window.Echo` (Laravel Echo)
-   `localStorage` (`roomLastRead:{roomId}`)
-   `meta[name="csrf-token"]` para CSRF

**Notas técnicas:**

-   `appendMessage` insere separadores de dia retroativos apenas para novas mensagens.
-   Usa mensagens temporárias (`temp_id`) até confirmação do servidor.
-   Garante que apenas mensagens da sala ativa são renderizadas.
-   `beforeunload` assegura limpeza de estado ao sair da página.

---

# Documentação Técnica — Views (Layouts)

## `resources/views/layouts/app.blade.php`

**Responsabilidade principal:**  
Layout base para utilizadores autenticados.  
Define a estrutura principal da aplicação (sidebar, header, conteúdo).

**Estrutura:**

-   **Head**

    -   Meta tags dinâmicas (`title`, `description`, `author`).
    -   Token CSRF (`meta[name="csrf-token"]`).
    -   CSS e JS via Vite (`resources/css/app.css`, `resources/js/app.js`).
    -   Stack opcional `@stack('head')` para scripts adicionais.

-   **Body**

    -   Classe base com suporte a _dark mode_.
    -   Atributo `data-auth-id` definido para utilizadores autenticados.
    -   Estrutura principal:
        -   Sidebar (`layouts.navigation`) incluída apenas para utilizadores autenticados.
        -   Header opcional (`$header`).
        -   Conteúdo principal (`@yield('content')` ou `$slot`).

-   **Footer**
    -   Stack `@stack('scripts')` para scripts adicionais no fim do body.

**Notas técnicas:**

-   Usa `@includeIf` para carregar a sidebar apenas se existir.
-   Suporta tanto `@yield('content')` como `$slot` (flexibilidade entre Blade sections e componentes).
-   Estrutura responsiva com `flex`.

---

## `resources/views/layouts/guest.blade.php`

**Responsabilidade principal:**  
Layout base para utilizadores não autenticados (área pública).  
Usado em páginas como login, registo, recuperação de password.

**Estrutura:**

-   **Head**

    -   Meta tags dinâmicas (`title`, `description`, `author`).
    -   Token CSRF (`meta[name="csrf-token"]`).
    -   Fonts externas (Bunny Fonts).
    -   CSS e JS via Vite (`resources/css/app.css`, `resources/js/app.js`).
    -   Stack opcional `@stack('head')`.

-   **Body**

    -   Estrutura centralizada verticalmente (`sm:justify-center`).
    -   Logo (`components.application-logo`).
    -   Conteúdo principal (`$slot`) dentro de card estilizado (`bg-white dark:bg-gray-800`, `shadow-md`, `rounded-lg`).

-   **Footer**
    -   Stack `@stack('scripts')` para scripts adicionais.

**Notas técnicas:**

-   Mantém consistência visual com o layout autenticado (dark mode, fontes).
-   Estrutura simplificada, sem sidebar.
-   Ideal para páginas standalone.

---

## `resources/views/layouts/navigation.blade.php`

**Responsabilidade principal:**  
Sidebar de navegação da aplicação.  
Mostra logo, lista de salas, lista de DMs e perfil/logout.

**Estrutura:**

-   **Logo**

    -   Link para `dashboard`.
    -   Inclui componente `x-application-logo` e nome da aplicação.

-   **Conteúdo da Sidebar**

    -   Inclui partials:
        -   `partials.sidebar-rooms` (lista de salas, com `rooms`).
        -   `partials.sidebar-dms` (lista de contactos diretos, com `directContacts`).

-   **Perfil / Logout**

    -   Inclui partial `partials.sidebar-profile`.

-   **Scripts**
    -   `@push('scripts')` → inclui `resources/js/navigation.js`.

**Notas técnicas:**

-   Usa `role="navigation"` para acessibilidade.
-   Estrutura fixa (`h-screen`, `border-r`).
-   Integra com `BadgeManager` via `navigation.js` para gestão de notificações.
-   Sidebar responsiva com scroll (`overflow-y-auto`).

---

# Documentação Técnica — Views (Partials)

## `resources/views/partials/sidebar-dms.blade.php`

**Responsabilidade principal:**  
Renderizar a lista de contactos diretos (DMs) na sidebar.  
Mostra avatar, nome e badge de mensagens não lidas.

**Estrutura:**

-   **Título** → "Diretas" com ícone SVG.
-   **Lista de contactos (`$directContacts`)**

    -   Cada contacto é um link para `dm.show`.
    -   Atributos:
        -   `data-user-id` → usado pelo JS (`navigation.js`) para identificar contacto.
        -   Classe `direct-contact` → usada para delegação de eventos.
    -   Conteúdo:
        -   Avatar (`$contact->avatar` ou fallback `ui-avatars.com`).
        -   Nome do contacto.
        -   Badge (`span.contact-unread`) → visível se `$contact->unread_count > 0`.
            -   Atributos `data-badge-type="dm"` e `data-badge-id`.

-   **Fallback**
    -   Se não houver contactos → mostra "Sem diretas".

**Integração com JS:**

-   `BadgeManager` usa `data-badge-type="dm"` e `data-badge-id` para aplicar/limpar badges.
-   Clique em `.direct-contact` limpa badge e atualiza `window.peerId`.

---

## `resources/views/partials/sidebar-profile.blade.php`

**Responsabilidade principal:**  
Mostrar perfil do utilizador autenticado e botão de logout.

**Estrutura:**

-   **Avatar** → `Auth::user()->avatar` ou fallback `ui-avatars.com`.
-   **Nome** → `Auth::user()->name`.
-   **Formulário de logout**
    -   Método `POST` para `route('logout')`.
    -   Inclui token CSRF.
    -   Botão estilizado com ícone SVG e texto "Sair".

**Notas técnicas:**

-   Posicionado no fundo da sidebar (`border-t`).
-   Usa classes utilitárias para dark mode.
-   Logout é imediato via POST (sem JS adicional).

---

## `resources/views/partials/sidebar-rooms.blade.php`

**Responsabilidade principal:**  
Renderizar a lista de salas na sidebar.  
Mostra avatar, nome e badge de mensagens não lidas.

**Estrutura:**

-   **Título** → "Salas" com ícone SVG.
-   **Lista de salas (`$rooms`)**

    -   Cada sala é um link para `rooms.show`.
    -   Atributos:
        -   `data-room-id-link` → usado pelo JS (`navigation.js`) para identificar sala.
        -   Classe `room-link` → usada para delegação de eventos.
    -   Conteúdo:
        -   Avatar (`$room->avatar` ou fallback `ui-avatars.com`).
        -   Nome da sala.
        -   Badge (`span.room-unread`) → visível se `$room->unread_count > 0`.
            -   Atributos `data-badge-type="room"` e `data-badge-id`.

-   **Fallback**
    -   Se não houver salas → mostra "Sem salas".

**Integração com JS:**

-   `BadgeManager` usa `data-badge-type="room"` e `data-badge-id` para aplicar/limpar badges.
-   Clique em `.room-link` limpa badge e atualiza `window.roomId`.

---

# Documentação Técnica — Views (Mensagens Diretas)

## `resources/views/dm/index.blade.php`

**Responsabilidade principal:**  
Página de listagem de contactos para mensagens diretas (DMs).  
Permite ao utilizador escolher um contacto e abrir a respetiva conversa.

**Estrutura:**

-   **Header**

    -   Slot `header` → título "Mensagens Diretas".

-   **Conteúdo principal**

    -   Lista de contactos (`$contacts`).
    -   Cada contacto é um link para `dm.show`.
    -   Conteúdo de cada item:
        -   Avatar (`$contact->avatar` ou fallback `ui-avatars.com`).
        -   Nome do contacto.
        -   Ícone de seta (SVG) para indicar navegação.
    -   Atributo `aria-label` para acessibilidade ("Abrir conversa com {nome}").

-   **Fallback**
    -   Se não houver contactos → mostra mensagem "Ainda não tens mensagens diretas."

**Notas técnicas:**

-   Usa classes utilitárias para dark mode.
-   Estrutura responsiva e acessível (`aria-label`, `aria-hidden`).
-   Integra com `navigation.js` e `BadgeManager` para gestão de notificações.

---

## `resources/views/dm/show.blade.php`

**Responsabilidade principal:**  
Página de conversa direta entre o utilizador autenticado e outro utilizador.  
Renderiza mensagens, permite envio de novas mensagens e integra com Echo/JS.

**Estrutura:**

-   **Wrapper principal (`#dm-app`)**

    -   Atributos `data-peer-id` (id do outro utilizador) e `data-auth-id` (id do utilizador autenticado).
    -   Usado pelo JS (`dm.js`) para identificar a thread ativa.

-   **Header da conversa**

    -   Mostra título "Conversa com {nome do utilizador}".
    -   Estilizado com `bg-white dark:bg-gray-800`.

-   **Janela de mensagens (`#dm-window`)**

    -   Role `log` e `aria-live="polite"` para acessibilidade.
    -   Itera sobre `$messages`.
    -   Renderiza cada mensagem com:
        -   Nome do remetente (se diferente do anterior).
        -   Corpo da mensagem.
        -   Hora (`H:i`).
        -   Estilo diferente para mensagens próprias (`bg-blue-500 text-white`) e recebidas (`bg-gray-100` / `dark:bg-gray-700`).
    -   Usa animação `animate-fadeInUp`.

-   **Formulário de envio (`#dm-form`)**

    -   Input de texto (`#dm-input`) com placeholder e `aria-label`.
    -   Botão de envio estilizado com ícone 📤.
    -   Inclui token CSRF.
    -   Integra com `dm.js` para envio via AJAX.

-   **Scripts**
    -   `@push('scripts')` → inclui `resources/js/dm.js`.

**Notas técnicas:**

-   O JS (`dm.js`) expõe `window.appendMessage` para renderização dinâmica.
-   Integra com `BadgeManager` para limpar badge da DM ativa.
-   Estrutura preparada para mensagens temporárias (`temp_id`) até confirmação do servidor.
-   Usa `animate-fadeIn` e `animate-fadeInUp` para transições suaves.

---

# Documentação Técnica — Views (Salas)

## `resources/views/rooms/index.blade.php`

**Responsabilidade principal:**  
Página de listagem de salas de chat.  
Permite visualizar todas as salas disponíveis e, se o utilizador for admin, criar novas salas.

**Estrutura:**

-   **Header**

    -   Slot `header` → título "Salas de Chat".

-   **Botão "Nova Sala"**

    -   Visível apenas para administradores (`auth()->user()?->isAdmin()`).
    -   Link para `rooms.create`.
    -   Estilizado como botão arredondado com ícone `+`.

-   **Lista de salas (`$rooms`)**

    -   Cada sala é um link para `rooms.show`.
    -   Conteúdo de cada item:
        -   Avatar (`$room->avatar` ou fallback `ui-avatars.com`).
        -   Nome da sala.
        -   Ícone de seta (SVG).
    -   Atributo `aria-label` para acessibilidade ("Entrar na sala {nome}").

-   **Fallback**
    -   Se não houver salas → mostra "Nenhuma sala criada ainda."

**Notas técnicas:**

-   Usa classes utilitárias para dark mode.
-   Estrutura responsiva e acessível (`aria-label`, `aria-hidden`).
-   Integra com `navigation.js` e `BadgeManager` para gestão de notificações.

---

## `resources/views/rooms/create.blade.php`

**Responsabilidade principal:**  
Página para criação de novas salas.  
Disponível apenas para administradores.

**Estrutura:**

-   **Header**

    -   Slot `header` → título "Criar Nova Sala".

-   **Formulário (`POST rooms.store`)**
    -   Inclui token CSRF.
    -   Campos:
        -   **Nome da Sala** (`name`) → obrigatório, com validação e mensagens de erro.
        -   **Avatar (URL opcional)** (`avatar`) → input de texto.
            -   Pré-visualização dinâmica (`#avatar-preview`) atualizada em tempo real.
            -   Fallback para `ui-avatars.com`.
    -   Botão "Criar" → estilizado com ícone `+`.

**Notas técnicas:**

-   Usa `old('name')` e `old('avatar')` para manter valores após erro de validação.
-   Pré-visualização de avatar atualiza via `oninput`.
-   Estrutura responsiva (`max-w-xl mx-auto`).

---

## `resources/views/rooms/invite.blade.php`

**Responsabilidade principal:**  
Página para convidar utilizadores para uma sala existente.

**Estrutura:**

-   **Header**

    -   Título "Convidar utilizadores para a sala: {nome}".

-   **Mensagem de sucesso**

    -   Mostrada se existir `session('success')`.
    -   Renderizada como alerta verde.

-   **Formulário (`POST rooms.invite.submit`)**
    -   Inclui token CSRF.
    -   Campo `user_id` → `select` com lista de utilizadores disponíveis.
    -   Botão "Convidar" → estilizado com classes utilitárias.

**Notas técnicas:**

-   Apenas utilizadores autorizados (via `RoomPolicy@invite`) podem aceder.
-   Estrutura responsiva (`max-w-2xl mx-auto`).
-   Integra com backend para adicionar utilizador ao pivot `room_user`.

---

## `resources/views/rooms/show.blade.php`

**Responsabilidade principal:**  
Página principal de uma sala de chat.  
Renderiza mensagens existentes, permite envio de novas mensagens, integra com Echo/JS (`room.js`) e respeita permissões de convite e eliminação.

**Estrutura:**

-   **Wrapper principal (`#room-app`)**

    -   Atributos `data-room-id` e `data-room-slug` → usados pelo JS (`room.js`) para identificar a sala ativa.
    -   Classe `flex flex-col` para layout vertical.
    -   Altura dinâmica (`h-[calc(100vh-8rem)]`) para ocupar viewport menos header.

-   **Header da sala**

    -   Mostra nome da sala (`$room->name`).
    -   Botão "+ Convidar" → visível apenas se o utilizador tiver permissão (`@can('invite', $room)`).
        -   Link para `rooms.invite`.
        -   Estilizado como link azul com ícone `+`.

-   **Janela de mensagens (`#messages`)**

    -   Role principal de log de mensagens.
    -   Itera sobre `$messages`.
    -   Para cada mensagem:
        -   `id="message-{id}"` → usado pelo JS para manipulação (ex: delete).
        -   Alinhamento condicional:
            -   Mensagens próprias → `items-end` (direita).
            -   Mensagens de outros → `items-start` (esquerda).
        -   Mostra nome do remetente apenas se diferente do anterior (`$isSameSender`).
        -   Corpo da mensagem com estilos distintos:
            -   Próprias → `bg-blue-500 text-white`.
            -   Recebidas → `bg-gray-100 dark:bg-gray-700`.
        -   Hora formatada (`H:i`).
        -   Botão "Apagar" → visível apenas se autorizado (`@can('delete', $message)`).

-   **Formulário de envio (`#message-form`)**

    -   `POST messages.store`.
    -   Inclui token CSRF.
    -   Campo oculto `room_id` com id da sala.
    -   Campo `textarea` (`#message-input`) para corpo da mensagem.
    -   Botão "Enviar" → estilizado com ícone 📤.
    -   Integra com `room.js` para envio via AJAX e renderização dinâmica.

-   **Scripts**
    -   `@push('scripts')` → inclui `resources/js/room.js`.

**Integração com JS (`room.js`):**

-   `window.roomId` e `window.roomSlug` são definidos a partir dos atributos do wrapper.
-   `room.js` gere:
    -   Renderização dinâmica de mensagens (`appendMessage`).
    -   Envio AJAX de novas mensagens.
    -   Subscrição Echo (`RoomMessageSent`).
    -   Gestão de badges (`BadgeManager`).
    -   Eliminação de mensagens via `DELETE /messages/{id}`.
    -   Limpeza de estado ao sair da página (`beforeunload`).

**Notas técnicas:**

-   Usa `@can` para respeitar permissões de `RoomPolicy` (convite) e `MessagePolicy` (delete).
-   Estrutura preparada para integração em tempo real com Echo.
-   Acessibilidade:
    -   Botões e links com `aria-label`.
    -   Mensagens renderizadas com contraste adequado em dark mode.
-   Animações (`animate-fadeIn`, `animate-fadeInUp`) melhoram UX.

---

# Documentação Técnica — Views (Dashboard & Welcome)

## `resources/views/dashboard.blade.php`

**Responsabilidade principal:**  
Página inicial para utilizadores autenticados.  
Mostra o papel (role) do utilizador e oferece atalhos para as principais áreas da aplicação.

**Estrutura:**

-   **Wrapper principal**

    -   Espaçamento vertical (`py-12`) e animação `animate-fadeIn`.
    -   Container central (`max-w-3xl mx-auto`).

-   **Card principal**

    -   Fundo branco (`bg-white`) ou escuro (`dark:bg-gray-800`).
    -   Texto adaptado ao tema (`text-gray-900 dark:text-gray-100`).
    -   Conteúdo:
        -   Mensagem: "Estás autenticado como {role}".
        -   Texto auxiliar: "Escolhe uma opção para começar".

-   **Ações disponíveis**
    -   **Ver Salas**
        -   Link para `rooms.index`.
        -   Botão azul arredondado com ícone de seta.
        -   `aria-label="Ver salas de chat"`.
    -   **Mensagens Diretas**
        -   Link para `dm.index`.
        -   Botão cinza arredondado com ícone de mensagens.
        -   `aria-label="Abrir mensagens diretas"`.

**Notas técnicas:**

-   Usa `Auth::user()->role` para mostrar o papel do utilizador.
-   Estrutura responsiva e acessível (`aria-label`, ícones SVG).
-   Integra com rotas principais da aplicação.

---

## `resources/views/welcome.blade.php`

**Responsabilidade principal:**  
Página inicial pública (landing page).  
Apresenta a aplicação e oferece opções de login ou registo.

**Estrutura:**

-   **Wrapper principal**

    -   Ocupa altura total (`min-h-screen`).
    -   Centraliza conteúdo (`flex items-center justify-center`).
    -   Fundo claro ou escuro (`bg-gray-100 dark:bg-gray-900`).
    -   Animação `animate-fadeIn`.

-   **Conteúdo central**

    -   Container (`max-w-md text-center space-y-6`).
    -   Título principal: "💬 Chat App".
    -   Subtítulo: "Sistema de comunicação interna para equipas — rápido, privado e em tempo real."

-   **Ações disponíveis**

    -   **Entrar**
        -   Link para `login`.
        -   Botão azul arredondado com ícone de seta.
        -   `aria-label="Entrar na aplicação"`.
    -   **Criar Conta**
        -   Link para `register`.
        -   Botão cinza arredondado com ícone `+`.
        -   `aria-label="Criar nova conta"`.

-   **Rodapé**
    -   Texto pequeno: "Desenvolvido por José G. durante estágio na InovCorp."

**Notas técnicas:**

-   Estrutura simples e responsiva.
-   Usa ícones SVG para reforçar ações.
-   Integra com rotas de autenticação (`login`, `register`).
-   Mantém consistência visual com dark mode.

---

# Documentação Técnica — Rotas

## `routes/api.php`

**Responsabilidade principal:**  
Definir rotas da API (prefixadas com `/api`).

**Rotas:**

-   `GET /api/ping` → retorna `{"pong": true}`.
    -   Útil para health-checks ou testes de conectividade.

---

## `routes/auth.php`

**Responsabilidade principal:**  
Gerir rotas de autenticação, registo, recuperação de password e verificação de email.

**Rotas para convidados (`middleware: guest`):**

-   `GET /register` → formulário de registo (`RegisteredUserController@create`).
-   `POST /register` → criar novo utilizador (`RegisteredUserController@store`).
-   `GET /login` → formulário de login (`AuthenticatedSessionController@create`).
-   `POST /login` → autenticar utilizador (`AuthenticatedSessionController@store`).
-   `GET /forgot-password` → formulário de recuperação (`PasswordResetLinkController@create`).
-   `POST /forgot-password` → enviar link de reset (`PasswordResetLinkController@store`).
-   `GET /reset-password/{token}` → formulário de nova password (`NewPasswordController@create`).
-   `POST /reset-password` → atualizar password (`NewPasswordController@store`).

**Rotas para autenticados (`middleware: auth`):**

-   `GET /verify-email` → prompt de verificação (`EmailVerificationPromptController`).
-   `GET /verify-email/{id}/{hash}` → verificar email (`VerifyEmailController`).
-   `POST /email/verification-notification` → reenviar email de verificação (`EmailVerificationNotificationController@store`).
-   `GET /confirm-password` → formulário de confirmação de password (`ConfirmablePasswordController@show`).
-   `POST /confirm-password` → confirmar password (`ConfirmablePasswordController@store`).
-   `PUT /password` → atualizar password (`PasswordController@update`).
-   `POST /logout` → terminar sessão (`AuthenticatedSessionController@destroy`).

**Notas técnicas:**

-   Usa `throttle:6,1` para limitar tentativas de verificação/envio de email.
-   Estrutura típica do Laravel Breeze/Jetstream.

---

## `routes/channels.php`

**Responsabilidade principal:**  
Definir canais de broadcast privados/presence para Laravel Echo.

**Canais:**

-   `room.{roomId}` → autorizado se o utilizador for membro da sala (`$user->rooms()->where('rooms.id', $roomId)->exists()`).
-   `user.{id}` → autorizado apenas se o utilizador autenticado tiver o mesmo id.

**Notas técnicas:**

-   Garante segurança no acesso a eventos em tempo real.
-   Usado por `RoomMessageSent` e `DirectMessageSent`.

---

## `routes/console.php`

**Responsabilidade principal:**  
Definir comandos Artisan customizados.

**Comandos:**

-   `php artisan inspire` → mostra uma citação inspiradora (`Inspiring::quote()`).
    -   Propósito: `"Display an inspiring quote"`.

**Notas técnicas:**

-   Exemplo de extensão de Artisan.
-   Pode ser expandido com comandos específicos da aplicação.

---

## `routes/web.php`

**Responsabilidade principal:**  
Definir rotas web da aplicação (com middleware `web`).

**Rotas principais:**

-   **Broadcasting**

    -   `Broadcast::routes(['middleware' => ['web', 'auth']])` → autenticação de canais privados.

-   **Página inicial (`/`)**

    -   Se autenticado → redireciona para `rooms.index`.
    -   Caso contrário → mostra view `welcome`.

-   **Teste de broadcasting (`/broadcast-test`)**

    -   Dispara `TestEvent`.
    -   Retorna `"Evento disparado!"`.

-   **Verificação de sessão (`/session-check`)**

    -   Retorna JSON com `auth_id` e `user`.
    -   Protegido por `auth`.

-   **Dashboard (`/dashboard`)**
    -   Mostra view `dashboard`.
    -   Protegido por `auth` e `verified`.

**Rotas protegidas (`middleware: auth`):**

-   **Perfil**

    -   `GET /profile` → editar perfil (`ProfileController@edit`).
    -   `PATCH /profile` → atualizar perfil (`ProfileController@update`).
    -   `DELETE /profile` → eliminar conta (`ProfileController@destroy`).

-   **Salas de chat**

    -   `Route::resource('rooms', RoomController::class)->only(['index', 'show', 'create', 'store'])`.
    -   `POST /rooms/{room}/read` → marcar mensagens como lidas (`RoomController@markActiveRead`).
    -   `GET /rooms/{room}/invite` → formulário de convite (`RoomController@inviteForm`).
    -   `POST /rooms/{room}/invite` → enviar convite (`RoomController@invite`).
    -   Convites protegidos por `can:invite,room`.

-   **Mensagens em sala**

    -   `Route::resource('messages', MessageController::class)->only(['store', 'destroy'])`.

-   **Mensagens diretas (DMs)**
    -   `GET /dm` → lista de contactos (`DirectMessageController@index`).
    -   `GET /dm/{user}` → conversa com utilizador (`DirectMessageController@show`).
    -   `POST /dm/{user}` → enviar mensagem (`DirectMessageController@store`).
    -   `POST /dm/{user}/read` → marcar mensagens como lidas (`DirectMessageController@markActiveRead`).

**Notas técnicas:**

-   Estrutura clara entre rotas públicas, autenticadas e broadcasting.
-   Integra com policies (`can:invite,room`).
-   Usa `require __DIR__ . '/auth.php'` para incluir rotas de autenticação.

---

# Documentação Técnica — Configuração

## `.env`

**Responsabilidade principal:**  
Definir variáveis de ambiente da aplicação Laravel.  
Controla comportamento do sistema em runtime (debug, base de dados, broadcasting, cache, etc.).

**Principais variáveis:**

-   **App**

    -   `APP_NAME=Laravel` → nome da aplicação.
    -   `APP_ENV=local` → ambiente (local, staging, production).
    -   `APP_KEY` → chave de encriptação usada pelo Laravel.
    -   `APP_DEBUG=true` → ativa modo debug.
    -   `APP_URL=http://chat-app.test` → URL base da aplicação.
    -   `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` → definições de idioma.

-   **Sessões**

    -   `SESSION_DRIVER=database` → sessões guardadas em BD.
    -   `SESSION_LIFETIME=120` → tempo de vida em minutos.
    -   `SESSION_DOMAIN=chat-app.test` → domínio associado.

-   **Logging**

    -   `LOG_CHANNEL=stack` → canal de logs.
    -   `LOG_LEVEL=debug` → nível de detalhe.

-   **Base de dados**

    -   `DB_CONNECTION=sqlite` → usa SQLite como BD.

-   **Cache & Queues**

    -   `CACHE_DRIVER=database` → cache em BD.
    -   `QUEUE_CONNECTION=sync` → filas síncronas.
    -   `BROADCAST_DRIVER=pusher` → broadcasting via Pusher.

-   **Redis/Memcached**

    -   Configurações para Redis (`phpredis`) e Memcached.

-   **Mail**

    -   `MAIL_MAILER=log` → emails registados em log.
    -   `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` → remetente padrão.

-   **Pusher**
    -   `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` → credenciais oficiais.
    -   Variáveis duplicadas para Vite (`VITE_PUSHER_APP_KEY`, `VITE_PUSHER_APP_CLUSTER`).

**Notas técnicas:**

-   `.env` nunca deve ser versionado em produção (contém segredos).
-   `APP_KEY` é crítico para encriptação — não deve ser alterado após deploy.
-   `SESSION_DRIVER=database` requer migration da tabela `sessions`.
