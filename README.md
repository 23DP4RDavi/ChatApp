# DoodleVerse — Mākslas un Tērzēšanas Platforma

Pilnvērtīga sociālā platforma ar reāllaika tērzēšanu, digitālo mākslas galeriju un balsošanas sistēmu. Izveidota ar Vue 3 un Laravel 11.

---

## Funkcionalitāte

- Digitālā zīmēšana ar dažādiem instrumentiem un formu atbalstu
- Mākslas galerija ar meklēšanu, filtrēšanu un arhīvu
- Nedēļas tēmu sistēma — katru nedēļu jauna zīmēšanas tēma
- Balsošanas sistēma zīmējumu novērtēšanai
- Reāllaika tērzēšana ar privātām un grupas sarunām
- Draugi — pieprasījumu sūtīšana, apstiprināšana, draugu saraksts
- Grupas kanāli ar lomām un piekļuves tiesībām
- Lietotāja profils ar avatāru
- Google OAuth pieteikšanās
- Valodas pārslēgšana — latviešu un angļu
- Administratora panelis lietotāju un satura pārvaldībai

---

## Tehnoloģiju steks

### Frontend
- Vue 3 ar Composition API
- Vuetify 3 — Material Design komponentu bibliotēka
- Vite — izstrādes serveris un būvēšanas rīks
- Axios — HTTP klients API pieprasījumiem
- Laravel Echo un Pusher JS — reāllaika WebSocket savienojums

### Backend
- Laravel 11 — PHP tīmekļa lietojumprogrammu ietvars
- Laravel Sanctum — API autentifikācija ar tokenizāciju
- Laravel Reverb — WebSocket serveris reāllaika apraides nodrošinājumam
- MySQL — relāciju datubāze

---

## Projekta struktūra

```
ChatApp/
  backend/      Laravel API serveris
  frontend/     Vue 3 lietotāja saskarne
```

Sīkāka informācija par katru daļu:
- [frontend/README.md](frontend/README.md) — detalizēts frontend failu apraksts
- [backend/README.md](backend/README.md) — detalizēts backend failu apraksts

---

## Uzstādīšana

### Priekšnosacījumi

- PHP 8.2+ ar Composer
- Node.js 18+ ar npm
- MySQL datubāze

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Konfigurējiet `.env` failu ar MySQL savienojuma datiem, pēc tam:

```bash
php artisan migrate
php artisan db:seed
php artisan reverb:start
php artisan queue:work
php artisan serve
```

### Frontend

```bash
cd frontend
npm install
```

Izveidojiet `.env` failu ar sekojošu saturu:

```env
VITE_API_URL=http://localhost:8000/api
VITE_REVERB_APP_KEY=your_reverb_key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

Pēc tam palaidiet:

```bash
npm run dev
```

Frontend būs pieejams uz `http://localhost:5173`.

---

## API galapunkti

### Autentifikācija
- `POST /api/register` — reģistrācija
- `POST /api/login` — pieteikšanās
- `POST /api/logout` — izrakstīšanās
- `GET /api/user` — pašreizējais lietotājs

### Zīmējumi
- `GET /api/drawings` — iegūt zīmējumu sarakstu
- `POST /api/drawings` — saglabāt jaunu zīmējumu
- `DELETE /api/drawings/{id}` — dzēst zīmējumu
- `POST /api/drawings/{id}/vote` — nobalsot
- `DELETE /api/drawings/{id}/unvote` — atsaukt balsi
- `GET /api/drawings/{id}/comments` — iegūt komentārus
- `POST /api/drawings/{id}/comments` — pievienot komentāru

### Sarunas un ziņojumi
- `GET /api/conversations` — iegūt sarunu sarakstu
- `POST /api/conversations` — izveidot sarunu
- `GET /api/conversations/{id}/messages` — iegūt ziņojumus
- `POST /api/conversations/{id}/messages` — nosūtīt ziņojumu

### Draugi
- `GET /api/friends` — draugu saraksts
- `POST /api/friends/request/{id}` — nosūtīt pieprasījumu
- `POST /api/friends/accept/{id}` — apstiprināt pieprasījumu
- `DELETE /api/friends/{id}` — noņemt draugu

### Nedēļas tēmas
- `GET /api/weekly-themes/current` — pašreizējā tēma
- `GET /api/weekly-themes/archive` — tēmu arhīvs

### Administrācija
- `GET /api/admin/users` — lietotāju saraksts
- `GET /api/admin/messages` — ziņojumu saraksts
- `GET /api/admin/drawings` — zīmējumu saraksts
- `GET /api/admin/conversations` — sarunu saraksts

---

## Kļūdu žurnāli

```bash
cat backend/storage/logs/laravel.log
```

---

## Licenze

Privāts mācību projekts, izveidots 2026. gadā.
