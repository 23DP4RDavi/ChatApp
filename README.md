# DoodleVerse

DoodleVerse ir sociāla zīmēšanas un saziņas platforma ar Vue 3 frontend un Laravel 11 backend. Lietotnē var veidot zīmējumus, publicēt tos galerijā, balsot, komentēt un sazināties privātās vai grupu sarunās.

## Galvenās iespējas

- Zīmēšanas redaktors ar līnijām, formām, aizpildi, tekstu un slāņa darbībām.
- Galerija ar meklēšanu, filtrēšanu, nedēļas tēmu režīmu un arhīvu.
- Balsošana un komentāri pie zīmējumiem.
- Privātās un grupu sarunas ar kanāliem un lomām.
- Draugu sistēma ar pieprasījumiem.
- Lietotāja profils, avatāra zīmēšana un iestatījumi.
- Reāllaika ziņojumu piegāde ar Laravel Reverb.

## Tehnoloģijas

### Frontend

- Vue 3
- Vuetify 3
- Vite
- Axios
- Laravel Echo

### Backend

- Laravel 11
- Laravel Sanctum
- Laravel Reverb
- MySQL

## Projekta struktūra

```text
ChatApp/
  backend/   Laravel API un datu slānis
  frontend/  Vue lietotāja saskarne
```

- [backend/README.md](backend/README.md) satur detalizētu servera puses arhitektūru un datu modeļa aprakstu.
- [frontend/README.md](frontend/README.md) satur detalizētu klienta puses arhitektūru.

## Ātrā palaišana

### Priekšnosacījumi

- PHP 8.2+
- Composer
- Node.js 18+
- npm
- MySQL

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Ja tiek izmantots reāllaiks un rindas apstrāde, atsevišķos termināļos palaidiet:

```bash
php artisan reverb:start
php artisan queue:work
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

## Datu bāzes savienojumi

Projektā ir viena relāciju datubāze, kur tabulas savā starpā saistās ar ārējām atslēgām. Galvenie savienojumi ir šādi:

- users 1:N drawings
- drawings 1:N votes
- drawings 1:N drawing_comments
- users 1:N drawing_comments
- users N:M conversations caur conversation_participants
- conversations 1:N messages
- group_channels 1:N messages
- conversations 1:N group_channels
- conversations 1:N group_roles
- conversations 1:N group_invites
- group_roles 1:N group_member_roles
- users 1:N group_member_roles
- weekly_themes 1:N drawings

Papildus:

- conversations.owner_id norāda uz users.id un pēc lietotāja dzēšanas tiek uzstādīts null.
- messages.reply_to_id norāda uz messages.id un pēc oriģinālā ziņojuma dzēšanas tiek uzstādīts null.
- votes tabulā balsojums tiek sasaistīts ar drawings caur drawing_id; balsošanas identitāte tiek glabāta laukā voter_identifier.

## Biežāk lietotie API maršruti

### Autentifikācija

- POST /api/register
- POST /api/login
- POST /api/logout
- GET /api/user

### Zīmējumi

- GET /api/drawings
- POST /api/drawings
- DELETE /api/drawings/{id}
- POST /api/drawings/{id}/vote
- GET /api/drawings/{id}/comments
- POST /api/drawings/{id}/comments

### Sarunas

- GET /api/conversations
- POST /api/conversations
- GET /api/conversations/{id}/messages
- POST /api/conversations/{id}/messages

### Draugi

- GET /api/friends
- POST /api/friends/request/{id}
- POST /api/friends/accept/{id}

## Žurnāli un diagnostika

```bash
type backend\storage\logs\laravel.log
```

## Piezīme

Šis ir privāts mācību projekts.
