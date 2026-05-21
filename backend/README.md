# Backend — ChatApp

Servera puse ir veidota ar Laravel 11 (PHP). Izmanto MySQL datubāzi, Laravel Sanctum autentifikācijai un Laravel Reverb reāllaika apraides nodrošinājumam.

---

## Galvenā struktūra

---

## Kontrolieri (`app/Http/Controllers/`)

### `AuthController.php`

Apstrādā reģistrāciju, pieteikšanos, izrakstīšanos un Google OAuth plūsmu. Izmanto Sanctum tokenu izsniegšanai.

### `AdminController.php`

Administratora API. Pārvalda lietotājus, ziņojumus, zīmējumus, sarunas un nedēļas tēmas. Pieejams tikai `is_admin = true` lietotājiem.

### `ConversationController.php`

Pārvalda privātās un grupas sarunas — izveidi, dalībnieku pievienošanu, sarunu saraksta iegūšanu.

### `MessageController.php`

Apstrādā ziņojumu sūtīšanu un saņemšanu. Izsauc `MessageSent` notikumu reāllaika apraides aktivizēšanai.

### `DrawingController.php`

Pārvalda zīmējumus — saglabāšanu, iegūšanu, dzēšanu, balsošanu un zīmējumu saistīšanu ar nedēļas tēmu.

### `DrawingCommentController.php`

Apstrādā zīmējumu komentārus — pievienošanu un dzēšanu.

### `FriendshipController.php`

Pārvalda draudzību — pieprasījumu sūtīšanu, apstiprināšanu, noraidīšanu un draugu saraksta iegūšanu.

### `GlobalChatController.php`

Globālā tērzēšanas kanāla ziņojumu apstrāde (nav saistīts ar konkrētu sarunu).

### `GroupChannelController.php`

Pārvalda grupas kanālus — izveidi, rediģēšanu, dzēšanu un piekļuves tiesību pārbaudi.

### `GroupInviteController.php`

Apstrādā grupas uzaicinājumus — ģenerē ielūguma saites un apstrādā to apstiprināšanu.

### `GroupRoleController.php`

Pārvalda grupas lomas — izveidi, atjaunināšanu, piešķiršanu dalībniekiem.

### `NotificationController.php`

Sniedz lietotāja paziņojumu sarakstu (piemēram, draudzības pieprasījumi).

### `WeeklyThemeController.php`

Pārvalda nedēļas tēmas — pašreizējās tēmas iegūšanu un arhīva skatīšanu.

---

## Modeļi (`app/Models/`)

### `User.php`

Lietotāja modelis. Lauki: `username`, `email`, `password`, `avatar`, `is_admin`, `google_id`. Relācijas: `drawings`, `friendships`, `conversations`.

### `Conversation.php`

Sarunas modelis. Var būt privāta vai grupas saruna. Lauki: `name`, `is_group`, `avatar`, `owner_id`. Relācijas: `participants`, `messages`, `channels`.

### `ConversationParticipant.php`

Sarunas dalībnieka modelis. Saista `User` ar `Conversation`.

### `Message.php`

Ziņojuma modelis. Lauki: `conversation_id`, `channel_id`, `user_id`, `body`, `type` (teksts, attēls, zīmējums). Relācija: `user`, `conversation`.

### `Drawing.php`

Zīmējuma modelis. Saglabā zīmēšanas ceļu datus JSON formātā. Lauki: `user_id`, `title`, `description`, `paths`, `theme_id`, `is_public`, `is_free`.

### `DrawingComment.php`

Zīmējuma komentāra modelis. Saista `Drawing` ar `User` un komentāra tekstu.

### `Friendship.php`

Draudzības modelis. Lauki: `requester_id`, `receiver_id`, `status` (pending/accepted/rejected).

### `GroupChannel.php`

Grupas kanāla modelis. Pieder `Conversation`. Lauki: `name`, `category`, `allowed_role_ids`.

### `GroupRole.php`

Grupas lomas modelis. Pieder `Conversation`. Lauki: `name`, `color`, `permissions`.

### `GroupMemberRole.php`

Saista `User` ar `GroupRole` konkrētā `Conversation`.

### `GroupInvite.php`

Grupas uzaicinājuma modelis. Satur unikālu `token` un saistīto `conversation_id`.

### `Vote.php`

Balsojuma modelis. Saista `User` ar `Drawing` (viens balss vienam zīmējumam).

### `WeeklyTheme.php`

Nedēļas tēmas modelis. Lauki: `name`, `description`, `emoji`, `color`, `start_date`, `end_date`.

---

## Notikumi (`app/Events/`)

### `MessageSent.php`

Laravel apraides notikums. Tiek izsaukts, kad tiek saglabāts jauns ziņojums. Pārraida datus Reverb kanālā reāllaika atjauninājumiem klientā.

---

## Pakalpojumu sniedzēji (`app/Providers/`)

### `AppServiceProvider.php`

Galvenais Laravel pakalpojumu sniedzējs. Šeit var reģistrēt globālus saistījumus un konfigurāciju.

---

## Maršruti (`routes/`)

### `api.php`

Visi REST API maršruti. Ietver autentifikācijas, sarunu, ziņojumu, zīmējumu, draugu, grupas un administratora galapunktus. Aizsargātie maršruti izmanto `auth:sanctum` vidusslāni.

### `channels.php`

Laravel apraides kanālu autorizācija. Definē, kuriem lietotājiem ir atļauts klausīties privātos Reverb kanālus.

### `web.php`

Tīmekļa maršruti. Satur tikai `/{any}` noķeršanas maršrutu, kas novirza uz Vue lietotni (SPA).

### `console.php`

Artisan konsoļu komandu definīcijas.

---

## Datubāzes migrācijas (`database/migrations/`)

Migrācijas veido šādas tabulas un izmaiņas:

- `users` — pamatstruktura, vēlāk pievienoti `username`, avatāra lauki, Google autentifikācijas lauki, `is_admin`
- `drawings` — zīmējumu tabula ar tēmu un apraksta laukiem
- `votes` — balsošanas tabula
- `friendships` — draudzību tabula
- `conversations` — sarunu tabula ar grupas laukiem, `owner_id`
- `conversation_participants` — sarunu dalībnieki
- `messages` — ziņojumi ar kanāla un Discord stila atribūtiem
- `personal_access_tokens` — Sanctum tokenu tabula
- `weekly_themes` — nedēļas tēmu tabula
- `drawing_comments` — zīmējumu komentāri
- `group_channels` — grupas kanāli ar atļauto lomu laukiem
- `group_roles` — grupas lomas
- `group_invites` — grupas uzaicinājumi
- `group_member_roles` — lomu piešķiršana grupas dalībniekiem

---

## Sēklas (`database/seeders/`)

### `DatabaseSeeder.php`

Galvenais sēklu fails. Izsauc visus pārējos sēklotājus.

### `WeeklyThemeSeeder.php`

Ievada datubāzē sākotnējās nedēļas tēmas.

---

## Konfigurācija (`config/`)

- `auth.php` — autentifikācijas sargātāju un nodrošinātāju konfigurācija
- `broadcasting.php` — Reverb savienojuma konfigurācija
- `cors.php` — CORS politika (atļauj pieprasījumus no frontenda)
- `database.php` — MySQL savienojuma konfigurācija
- `sanctum.php` — Sanctum tokenu un stateful domēnu konfigurācija
- `reverb.php` — Laravel Reverb WebSocket servera konfigurācija

---

## Svarīgi faili

- `artisan` — Laravel komandrindas rīks
- `composer.json` — PHP atkarības un automatiskais ielādētājs
- `phpunit.xml` — PHPUnit testu konfigurācija
- `bootstrap/app.php` — Lietotnes inicializācija, vidusslāņu reģistrācija
