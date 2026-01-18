# DoodleVerse - Mākslas un Čata Platforma

Pilnvērtīga sociālā platforma ar reāllaika čatu, digitālo mākslas galeriju un balsošanas sistēmu. Izveidota ar Vue 3 un Laravel.

## Funkcionalitāte

- **Digitālā Zīmēšana** - Izveidojiet mākslas darbus ar iekļauto zīmēšanas rīku
- **Mākslas Galerija** - Pārlūkojiet, meklējiet un kārtojiet mākslas darbus
- **Balsošanas Sistēma** - Novērtējiet un atbalstiet jūsu mīļākos mākslas darbus
- **Reāllaika Čats** - Sazināties ar kopienu tūlītējos ziņojumos
- **Autentifikācija** - Drošs lietotāju reģistrēšanās un pieteikšanās process
- **Statistika** - Reāllaika statistika par mākslas darbiem, balsojumiem un lietotājiem

## Tehnoloģiju Steks

### Frontend
- **Vue 3** - Progresīvais JavaScript ietvars
- **Vuetify 3** - Material Design komponentu bibliotēka
- **Vite** - Ātrs izstrādes serveris un būvēšanas rīks
- **Axios** - HTTP klients API pieprasījumiem

### Backend
- **Laravel 10** - PHP tīmekļa aplikāciju ietvars
- **Laravel Sanctum** - API autentifikācija ar tokenizāciju
- **SQLite** - Viegla datu bāze izstrādei
- **Laravel Herd** - Vietējās izstrādes vide

## Priekšnosacījumi

- **Laravel Herd** - Ietver PHP 8.4+, Composer, un Nginx
- **Node.js** - Versija 16+ ar npm
- **Pārlūkprogramma** - Moderns tīmekļa pārlūks

## Uzstādīšana

### 1. Klonēt Repozitoriju

```bash
cd C:\Users\sarmi\Desktop
# Projekts jau atrodas ChatApp mapē
```

### 2. Backend Uzstādīšana

```powershell
# Pāriet uz backend mapi
cd ChatApp_backend_fresh

# Uzstādīt atkarības
herd composer install

# Kopēt vides konfigurāciju
cp .env.example .env

# Ģenerēt aplikācijas atslēgu
php artisan key:generate

# Izveidot SQLite datu bāzi
New-Item -ItemType File -Path database\database.sqlite -Force

# Palaist migrācijas
php artisan migrate

# Saistīt ar Herd (lai būtu pieejams kā chatapp-api.test)
herd link chatapp-api
```

### 3. Frontend Uzstādīšana

```powershell
# Pāriet uz frontend mapi
cd ..\frontend

# Uzstādīt atkarības
npm install

# Pārbaudīt .env failu (jābūt VITE_API_URL=http://chatapp-api.test/api)
```

## Palaišana

### Startēt Backend

Backend automātiski darbojas caur Laravel Herd:
- **URL**: http://chatapp-api.test
- **API**: http://chatapp-api.test/api

Nav nepieciešams palaist `php artisan serve` - Herd automātiski apkalpo projektu!

### Startēt Frontend

```powershell
cd frontend
npm run dev
```

Frontend būs pieejams uz: http://localhost:3000

## Projekta Struktūra

```
ChatApp/
├── ChatApp_backend_fresh/          # Laravel backend
│   ├── app/
│   │   ├── Http/Controllers/Api/   # API kontrolieri
│   │   │   ├── AuthController.php  # Autentifikācija
│   │   │   ├── DrawingController.php # Zīmējumu CRUD
│   │   │   └── MessageController.php # Čata ziņojumi
│   │   └── Models/                 # Eloquent modeļi
│   │       ├── User.php
│   │       ├── Drawing.php
│   │       ├── Vote.php
│   │       └── Message.php
│   ├── database/
│   │   ├── migrations/             # Datu bāzes migrācijas
│   │   └── database.sqlite         # SQLite datu bāze
│   └── routes/
│       └── api.php                 # API maršruti
│
└── frontend/                       # Vue 3 frontend
    ├── src/
    │   ├── components/             # Vue komponenti
    │   │   ├── AppHeader/
    │   │   ├── AppFooter/
    │   │   ├── ChatBox/
    │   │   ├── DrawingCard/
    │   │   ├── GalleryGrid/
    │   │   └── EmptyState/
    │   ├── views/                  # Lappuses
    │   │   ├── Home.vue           # Sākumlapa
    │   │   ├── Gallery.vue        # Mākslas galerija
    │   │   ├── Draw.vue           # Zīmēšanas rīks
    │   │   └── Chat.vue           # Čata istaba
    │   ├── router/                # Vue Router
    │   ├── services/              # API servisi
    │   └── styles/                # CSS stili
    └── package.json
```

## API Galapunkti

### Autentifikācija
- `POST /api/register` - Reģistrēt jaunu lietotāju
- `POST /api/login` - Pieteikties
- `POST /api/logout` - Izrakstīties
- `GET /api/user` - Iegūt autentificēto lietotāju

### Zīmējumi
- `GET /api/drawings` - Iegūt visus zīmējumus (paginēts)
- `POST /api/drawings` - Izveidot jaunu zīmējumu
- `GET /api/drawings/{id}` - Iegūt konkrētu zīmējumu
- `DELETE /api/drawings/{id}` - Dzēst zīmējumu
- `GET /api/drawings/{id}/check-vote` - Pārbaudīt vai lietotājs ir balsojis
- `POST /api/drawings/{id}/vote` - Balsot par zīmējumu
- `DELETE /api/drawings/{id}/unvote` - Atcelt balsi

### Ziņojumi
- `GET /api/messages` - Iegūt visas čata ziņas
- `POST /api/messages` - Nosūtīt jaunu ziņu
- `GET /api/messages/new` - Iegūt jaunās ziņas (polling)
- `DELETE /api/messages/{id}` - Dzēst ziņu

### Citi
- `GET /api/health` - Veselības pārbaude
- `GET /api/stats` - Globālā statistika

## Funkcijas Lietošana

### 1. Reģistrācija un Pieteikšanās
- Apmeklējiet http://localhost:3000
- Noklikšķiniet uz "Get Started" vai "Join Island"
- Reģistrējieties ar lietotājvārdu un paroli
- Pēc reģistrācijas automātiski tiks pieteikts

### 2. Zīmēšana
- Doties uz Draw lapu navigācijas izvēlnē
- Izmantojiet zīmēšanas rīkus kreisajā pusē
- Izvēlieties krāsas, otas izmēru un darbarīkus
- Saglabājiet mākslas darbu ar nosaukumu

### 3. Galerija
- Pārlūkojiet visus mākslas darbus Gallery lapā
- Izmantojiet meklēšanas joslu, lai filtrētu pēc nosaukuma vai mākslinieka
- Kārtojiet pēc "Newest" vai "Most Loved"
- Noklikšķiniet uz sirds ikonas, lai balsotu

### 4. Čats
- Apmeklējiet Chat lapu
- Rakstiet ziņas čata lodziņā
- Ziņas atjauninās automātiski katras 3 sekundes

## Atkļūdošana

### Laravel Debugbar

Projekts ir aprīkots ar Laravel Debugbar izstrādei:
- Apmeklējiet jebkuru API galapunktu pārlūkprogrammā
- Debugbar parādīs SQL vaicājumus, veiktspēju, un pieprasījuma informāciju
- Pieejams tikai kad `APP_DEBUG=true` .env failā

### Kļūdu Žurnāli

Backend žurnāli:
```powershell
# Skatīt Laravel žurnālus
cd ChatApp_backend_fresh
cat storage\logs\laravel.log
```

## Konfigurācija

### Backend (.env)
```env
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=C:\Users\sarmi\Desktop\ChatApp_backend_fresh\database\database.sqlite
```

### Frontend (.env)
```env
VITE_API_URL=http://chatapp-api.test/api
```

## Būvēšana Produkcijai

### Frontend
```powershell
cd frontend
npm run build
```

Būvētie faili būs `dist/` mapē.

### Backend
```powershell
cd ChatApp_backend_fresh
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Atbalsts

Ja rodas problēmas:
1. Pārbaudiet vai Herd darbojas: `herd list`
2. Pārbaudiet vai backend ir saistīts: `herd links`
3. Pārbaudiet vai datu bāze eksistē: `ls database\database.sqlite`
4. Pārbaudiet žurnālus: `storage\logs\laravel.log`

## Licenze

Šis projekts ir privāts mācību projekts.

## Autors

Izveidots 2026. gadā kā pilnvērtīga Vue 3 + Laravel 10 aplikācija.
