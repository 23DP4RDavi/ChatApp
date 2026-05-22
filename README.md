# DoodleVerse

DoodleVerse ir sociāla zīmēšanas un saziņas platforma, kur vienā lietotnē apvienoti digitālie zīmējumi, galerija, komentāri, balsošana, privātās sarunas, grupu kanāli, draugu sistēma un lietotāju profili. Projekts ir sadalīts divās skaidrās daļās: frontend pusē atrodas Vue lietotāja saskarne, bet backend pusē Laravel API, autentifikācija, datu modelis un reāllaika ziņojumu loģika.

## Ko šis projekts dara

Lietotne ir veidota tā, lai lietotājs varētu:

- izveidot zīmējumu ar dažādiem rīkiem un saglabāt to galerijā vai sarunā;
- publicēt zīmējumu kā profila avatāru;
- pārlūkot galeriju, meklēt darbus un balsot par tiem;
- komentēt zīmējumus un skatīt nedēļas tēmu arhīvu;
- sūtīt privātas ziņas vai piedalīties grupu sarunās;
- pārvaldīt draugus, ielūgumus un paziņojumus;
- lietot iestatījumus, valodas pārslēgšanu un pieejamības opcijas;
- administrēt lietotājus, ziņojumus, zīmējumus, sarunas un tēmas.

## Galvenās iespējas

- Pilns zīmēšanas audekls ar līnijām, formām, aizpildi, tekstu, dzēšgumiju un otām.
- Avatāra zīmēšanas režīms profila iestatījumos.
- Galerija ar filtrēšanu, meklēšanu, populāro darbu kārtošanu un arhīvu.
- Nedēļas tēmu sistēma ar tematisku zīmējumu publicēšanu.
- Privātās un grupu sarunas ar kanāliem, lomām un uzaicinājumiem.
- Draugu pieprasījumi un draugu saraksts.
- Balsošana un komentēšana pie zīmējumiem.
- Administrēšanas panelis ar satura un lietotāju pārvaldību.
- Reāllaika atjauninājumi ar Laravel Reverb.

## Tehnoloģijas

### Frontend

- Vue 3
- Vuetify 3
- Vite
- Vue Router
- Axios
- Laravel Echo

### Backend

- Laravel 11
- Laravel Sanctum
- Laravel Reverb
- MySQL

## Projekta uzbūve

Projekts ir veidots kā klasiska klienta un servera arhitektūra:

1. Frontend apstrādā saskarni, navigāciju un lietotāja mijiedarbību.
2. Backend apstrādā API pieprasījumus, validāciju, datu glabāšanu un autorizāciju.
3. MySQL glabā lietotājus, zīmējumus, sarunas, ziņojumus, draudzības saites, grupu struktūru un tēmas.
4. Reverb nodrošina reāllaika notikumus, piemēram, jaunu ziņu piegādi.

## Projekta struktūra

```text
ChatApp/
  backend/   Laravel API un datu slānis
  frontend/  Vue lietotāja saskarne
```

- [backend/README.md](backend/README.md) satur detalizētu backend aprakstu, datu relācijas un API uzvedību.
- [frontend/README.md](frontend/README.md) satur detalizētu frontend aprakstu, skatus un datu plūsmu.

## Startēšana lokāli

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

Ja izmanto reāllaika ziņojumus vai rindas apstrādi, atsevišķos termināļos palaid:

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

Frontend parasti darbojas uz `http://localhost:5173`, bet backend uz `http://localhost:8000` vai citu norādītu adresi.

## Datu plūsma starp frontend un backend

1. Lietotājs atver skatu frontend pusē.
2. Skats izmanto `api.js` un izsauc backend API.
3. Backend pārbauda autentifikāciju, validē datus un saglabā tos datubāzē.
4. Pēc veiksmīga pieprasījuma frontend atjauno Vue stāvokli un pārzīmē attēlojumu.
5. Reāllaika notikumi tiek saņemti caur Reverb, lai ziņojumi un atjauninājumi parādītos uzreiz.

## Datu bāzes savienojumi īsumā

Projektā tabulas ir sasaistītas ar ārējām atslēgām. Galvenās attiecības ir:

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
- votes tabulā balsojums ir piesaistīts drawing_id, bet identitāte tiek glabāta laukā voter_identifier.

## Biežāk lietotie API galapunkti

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
- DELETE /api/drawings/{id}/vote
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
- DELETE /api/friends/{id}

### Administrācija

- GET /api/admin/stats
- GET /api/admin/users
- GET /api/admin/messages
- GET /api/admin/drawings
- GET /api/admin/conversations
- GET /api/admin/themes

## Žurnāli un diagnostika

```bash
type backend\storage\logs\laravel.log
```

## Piezīme

Šis ir privāts mācību projekts.