# ChatApp - Projekta Apraksts

## 1. VISPĀRĪGA INFORMĀCIJA

### 1.1 Projekta nosaukums
**ChatApp (DoodleVerse)** - Sociālā mākslas un čata platforma

### 1.2 Projekta mērķis
Izveidot pilnvērtīgu tīmekļa lietojumprogrammu, kas apvieno digitālās mākslas galeriju, reāllaika čatu un sociālo tīklu funkcionalitāti, ļaujot lietotājiem dalīties ar saviem mākslas darbiem, sazināties un veidot draudzības.

### 1.3 Projekta apjoms
Projekts ietver:
- Frontend lietojumprogrammu (Vue.js)
- Backend API (Laravel)
- Datu bāzi (SQLite)
- Autentifikācijas sistēmu
- Reāllaika čata funkcionalitāti
- Digitālās zīmēšanas rīku
- Draudzības pieprasījumu sistēmu
- Balsošanas mehānismu

---

## 2. FUNKCIONĀLĀS PRASĪBAS

### 2.1 Lietotāju autentifikācija
- **Reģistrācija**: Jaunu lietotāju reģistrācija ar vārdu, lietotājvārdu, e-pastu un paroli
- **Pieteikšanās**: Drošs autentifikācijas process ar sesiju pārvaldību
- **Izrakstīšanās**: Sesijas beigšana un drošības tokenu dzēšana
- **Sesiju pārvaldība**: Laravel Sanctum izmantošana API autentifikācijai

### 2.2 Digitālā māksla un zīmēšana
- **Zīmēšanas rīks**: Iekļauts digitālais zīmēšanas rīks ar dažādām krāsām un instrumentiem
- **Mākslas darbu augšupielāde**: Lietotāji var saglabāt savus radītos mākslas darbus
- **Galerijas skatīšana**: Visu lietotāju mākslas darbu pārlūkošana
- **Meklēšana un kārtošana**: Mākslas darbu meklēšana un kārtošana pēc dažādiem kritērijiem
- **Mākslas darbu dzēšana**: Lietotāji var dzēst savus mākslas darbus

### 2.3 Balsošanas sistēma
- **Balsu piešķiršana**: Lietotāji var balsot par mākslas darbiem
- **Balsu atsaukšana**: Iespēja atsaukt savu balsi
- **Balsu skaitīšana**: Reāllaika balsu skaita attēlošana
- **Balsošanas pārbaude**: Sistēma pārbauda, vai lietotājs jau ir balsojis

### 2.4 Draudzības sistēma
- **Lietotāju meklēšana**: Meklēt citus lietotājus pēc lietotājvārda
- **Draudzības pieprasījumi**: Nosūtīt draudzības pieprasījumus citiem lietotājiem
- **Pieprasījumu apstiprinājums**: Apstiprināt vai noraidīt saņemtos pieprasījumus
- **Draugu saraksts**: Skatīt apstiprināto draugu sarakstu
- **Gaidošie pieprasījumi**: Skatīt nosūtītos un saņemtos gaidošos pieprasījumus
- **Draugu dzēšana**: Noņemt cilvēkus no draugu saraksta

### 2.5 Čata sistēma
- **Privātie ziņojumi**: Sūtīt ziņojumus draugiem
- **Sarunu saraksts**: Skatīt visas aktīvās sarunas
- **Ziņojumu vēsture**: Ielādēt iepriekšējās ziņojumu vēsturi
- **Reāllaika atjaunināšana**: Jaunu ziņojumu automātiska parādīšana
- **Ziņojumu dzēšana**: Iespēja dzēst savus ziņojumus

### 2.6 Statistika
- **Lietotāju statistika**: Kopējais lietotāju skaits
- **Mākslas darbu statistika**: Kopējais mākslas darbu skaits
- **Aktīvo lietotāju rādītāji**: Tiešsaistē esošo lietotāju skaits

---

## 3. TEHNISKĀ SPECIFIKĀCIJA

### 3.1 Tehnoloģiju steks

#### Frontend
- **Vue 3**: Progresīvais JavaScript ietvars lietotāja interfeisa izveidei
- **Vuetify 3**: Material Design komponentu bibliotēka
- **Vue Router**: Maršrutēšanas pārvaldība vienlapas lietojumprogrammā
- **Pinia**: Stāvokļa pārvaldības bibliotēka
- **Axios**: HTTP klients API pieprasījumiem
- **Vite**: Moderns būvēšanas rīks un izstrādes serveris

#### Backend
- **Laravel 10**: PHP tīmekļa aplikāciju ietvars
- **Laravel Sanctum**: API autentifikācija ar tokenizāciju
- **PHP 8.4+**: Programmēšanas valoda
- **Composer**: PHP atkarību pārvaldnieks

#### Datu bāze
- **SQLite**: Viegla, uz failu balstīta datu bāze
- **Eloquent ORM**: Laravel datu bāzes abstakcijas slānis

#### Izstrādes vide
- **Laravel Herd**: Vietējās izstrādes vide (ietver PHP, Nginx, Composer)
- **Node.js 16+**: JavaScript izpildlaiks frontend izstrādei
- **npm**: Node.js pakotņu pārvaldnieks

### 3.2 Sistēmas arhitektūra

#### Arhitektūras modelis
Projekts izmanto **klienta-servera arhitektūru** ar atdalītu frontend un backend:

```
┌─────────────────────────────────────────────────┐
│              FRONTEND (Vue 3)                   │
│  ┌──────────────────────────────────────────┐  │
│  │  Views: Home, Gallery, Draw, Chat        │  │
│  │  Components: AppHeader, ChatBox, etc.    │  │
│  │  Router: Maršrutēšana                     │  │
│  │  Services: API servisi                    │  │
│  │  Pinia: Stāvokļa pārvaldība              │  │
│  └──────────────────────────────────────────┘  │
└─────────────────────────────────────────────────┘
                      ↕ HTTP/HTTPS (REST API)
┌─────────────────────────────────────────────────┐
│              BACKEND (Laravel 10)               │
│  ┌──────────────────────────────────────────┐  │
│  │  Routes: API maršruti                     │  │
│  │  Controllers: Biznesa loģika             │  │
│  │  Models: Datu modeļi                      │  │
│  │  Middleware: Autentifikācija, CORS       │  │
│  └──────────────────────────────────────────┘  │
└─────────────────────────────────────────────────┘
                      ↕
┌─────────────────────────────────────────────────┐
│          DATU BĀZE (SQLite)                     │
│  Tables: users, drawings, votes, messages,     │
│          friendships, personal_access_tokens    │
└─────────────────────────────────────────────────┘
```

### 3.3 Datu bāzes struktūra

#### Tabula: users
| Lauks | Tips | Apraksts |
|-------|------|----------|
| id | INTEGER | Primārā atslēga |
| name | TEXT | Lietotāja vārds |
| username | TEXT | Unikāls lietotājvārds |
| email | TEXT | Unikāls e-pasts |
| password | TEXT | Šifrēta parole |
| created_at | TIMESTAMP | Izveidošanas datums |
| updated_at | TIMESTAMP | Atjaunošanas datums |

#### Tabula: friendships
| Lauks | Tips | Apraksts |
|-------|------|----------|
| id | INTEGER | Primārā atslēga |
| user_id | INTEGER | Pieprasītāja ID (ārējā atslēga) |
| friend_id | INTEGER | Drauga ID (ārējā atslēga) |
| status | TEXT | Statuss: pending, accepted, blocked |
| created_at | TIMESTAMP | Izveidošanas datums |
| updated_at | TIMESTAMP | Atjaunošanas datums |

#### Tabula: drawings
| Lauks | Tips | Apraksts |
|-------|------|----------|
| id | INTEGER | Primārā atslēga |
| user_id | INTEGER | Autora ID (ārējā atslēga) |
| title | TEXT | Mākslas darba nosaukums |
| image_data | TEXT | Base64 kodēts attēls |
| votes_count | INTEGER | Balsu skaits (noklusējums: 0) |
| created_at | TIMESTAMP | Izveidošanas datums |
| updated_at | TIMESTAMP | Atjaunošanas datums |

#### Tabula: votes
| Lauks | Tips | Apraksts |
|-------|------|----------|
| id | INTEGER | Primārā atslēga |
| user_id | INTEGER | Balsotāja ID (ārējā atslēga) |
| drawing_id | INTEGER | Mākslas darba ID (ārējā atslēga) |
| created_at | TIMESTAMP | Izveidošanas datums |
| updated_at | TIMESTAMP | Atjaunošanas datums |

#### Tabula: messages
| Lauks | Tips | Apraksts |
|-------|------|----------|
| id | INTEGER | Primārā atslēga |
| user_id | INTEGER | Sūtītāja ID (ārējā atslēga) |
| content | TEXT | Ziņojuma saturs |
| created_at | TIMESTAMP | Izveidošanas datums |
| updated_at | TIMESTAMP | Atjaunošanas datums |

#### Tabula: personal_access_tokens
| Lauks | Tips | Apraksts |
|-------|------|----------|
| id | INTEGER | Primārā atslēga |
| tokenable_type | TEXT | Token īpašnieka tips |
| tokenable_id | INTEGER | Token īpašnieka ID |
| name | TEXT | Token nosaukums |
| token | TEXT | Unikāls token |
| abilities | TEXT | Token tiesības |
| created_at | TIMESTAMP | Izveidošanas datums |
| updated_at | TIMESTAMP | Atjaunošanas datums |

### 3.4 API galapunkti

#### Autentifikācija
- `POST /api/register` - Jauna lietotāja reģistrācija
- `POST /api/login` - Lietotāja pieteikšanās
- `POST /api/logout` - Lietotāja izrakstīšanās
- `GET /api/user` - Autentificētā lietotāja informācija

#### Mākslas darbi
- `GET /api/drawings` - Visu mākslas darbu saraksts (ar pagināciju)
- `GET /api/drawings/{id}` - Konkrēta mākslas darba informācija
- `POST /api/drawings` - Jauna mākslas darba izveidošana
- `DELETE /api/drawings/{id}` - Mākslas darba dzēšana
- `POST /api/drawings/{id}/vote` - Balsot par mākslas darbu
- `DELETE /api/drawings/{id}/vote` - Atsaukt balsi
- `GET /api/drawings/{id}/check-vote` - Pārbaudīt balsošanas statusu

#### Draudzība
- `GET /api/friends` - Draugu saraksts
- `GET /api/friends/pending` - Gaidošie pieprasījumi
- `POST /api/friends/request` - Nosūtīt draudzības pieprasījumu
- `POST /api/friends/{id}/accept` - Apstiprināt pieprasījumu
- `DELETE /api/friends/{id}/reject` - Noraidīt pieprasījumu
- `DELETE /api/friends/{id}` - Dzēst draugu
- `GET /api/users/search` - Meklēt lietotājus

#### Ziņojumi un sarunas
- `GET /api/conversations` - Sarunu saraksts
- `POST /api/conversations` - Izveidot vai iegūt sarunu
- `GET /api/conversations/{id}/messages` - Sarunas ziņojumi
- `POST /api/conversations/{id}/messages` - Nosūtīt ziņojumu
- `GET /api/conversations/{id}/messages/new` - Iegūt jaunus ziņojumus
- `DELETE /api/messages/{id}` - Dzēst ziņojumu

#### Statistika
- `GET /api/stats` - Sistēmas statistika
- `GET /api/health` - API veselības pārbaude

---

## 4. DROŠĪBAS PRASĪBAS

### 4.1 Autentifikācija un autorizācija
- **Laravel Sanctum**: Token-balstīta autentifikācija
- **Paroles šifrēšana**: Bcrypt algoritms paroles drošai glabāšanai
- **CSRF aizsardzība**: Cross-Site Request Forgery aizsardzība
- **CORS konfigurācija**: Pareiza Cross-Origin Resource Sharing iestatīšana

### 4.2 Datu validācija
- **Frontend validācija**: Vuetify veidlapu validācija
- **Backend validācija**: Laravel Request validācija visiem ienākošajiem datiem
- **SQL injekciju aizsardzība**: Eloquent ORM automātiska parametru saistīšana

### 4.3 Piekļuves kontrole
- **Middleware autentifikācija**: Aizsargāti maršruti pieejami tikai autentificētiem lietotājiem
- **Resursu īpašumtiesību pārbaude**: Lietotāji var rediģēt/dzēst tikai savus resursus
- **Draudzības pārbaude**: Privātās ziņojumu funkcijas pieejamas tikai draugiem

---

## 5. UZSTĀDĪŠANA UN PALAIŠANA

### 5.1 Priekšnosacījumi
- Laravel Herd (ietver PHP 8.4+, Composer, Nginx)
- Node.js 16+ ar npm
- Moderns tīmekļa pārlūks

### 5.2 Uzstādīšanas process

#### Backend uzstādīšana
```powershell
# Pāriet uz backend mapi
cd ChatApp\backend

# Uzstādīt Composer atkarības
herd composer install

# Kopēt vides konfigurāciju
copy .env.example .env

# Ģenerēt aplikācijas atslēgu
php artisan key:generate

# Izveidot SQLite datu bāzi
New-Item -ItemType File -Path database\database.sqlite -Force

# Palaist migrācijas
php artisan migrate

# Saistīt ar Herd
herd link chatapp-api
```

#### Frontend uzstādīšana
```powershell
# Pāriet uz frontend mapi
cd ..\frontend

# Uzstādīt npm atkarības
npm install

# Pārbaudīt .env failu
# VITE_API_URL=http://chatapp-api.test/api
```

### 5.3 Palaišana

#### Backend
Backend automātiski darbojas caur Laravel Herd:
- URL: http://chatapp-api.test
- API: http://chatapp-api.test/api

#### Frontend
```powershell
cd frontend
npm run dev
```
Frontend būs pieejams uz: http://localhost:3001/

---

## 6. PROJEKTA STRUKTŪRA

### 6.1 Backend struktūra
```
backend/
├── app/
│   ├── Console/              # Konsoles komandas
│   ├── Exceptions/           # Izņēmumu apstrāde
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/         # API kontrolieri
│   │   │       ├── AuthController.php
│   │   │       ├── DrawingController.php
│   │   │       ├── FriendshipController.php
│   │   │       ├── MessageController.php
│   │   │       └── ConversationController.php
│   │   ├── Middleware/      # Middleware klases
│   │   └── Kernel.php       # HTTP kodola konfigurācija
│   ├── Models/              # Eloquent modeļi
│   │   ├── User.php
│   │   ├── Drawing.php
│   │   ├── Friendship.php
│   │   ├── Message.php
│   │   └── Vote.php
│   └── Providers/           # Servisu piegādātāji
├── config/                  # Konfigurācijas faili
├── database/
│   ├── migrations/          # Datu bāzes migrācijas
│   └── database.sqlite      # SQLite datu bāze
├── routes/
│   ├── api.php             # API maršruti
│   └── web.php             # Web maršruti
└── vendor/                 # Composer atkarības
```

### 6.2 Frontend struktūra
```
frontend/
├── src/
│   ├── components/         # Vue komponenti
│   │   ├── AppHeader/      # Galvenes komponents
│   │   ├── AppFooter/      # Kājenes komponents
│   │   ├── ChatBox/        # Čata komponents
│   │   ├── DrawingCard/    # Mākslas darba karte
│   │   ├── GalleryGrid/    # Galerijas režģis
│   │   └── EmptyState/     # Tukšā stāvokļa komponents
│   ├── views/              # Skata komponenti (lapas)
│   │   ├── Home.vue        # Sākumlapa
│   │   ├── Gallery.vue     # Galerijas lapa
│   │   ├── Draw.vue        # Zīmēšanas lapa
│   │   ├── Chat.vue        # Čata lapa
│   │   └── Friends.vue     # Draugu lapa
│   ├── router/             # Vue Router konfigurācija
│   ├── services/           # API servisi
│   │   └── api.js          # Axios konfigurācija
│   ├── styles/             # CSS stili
│   ├── App.vue             # Galvenais App komponents
│   └── main.js             # Lietojumprogrammas ieejas punkts
└── package.json            # npm atkarības
```

---

## 7. TESTĒŠANA

### 7.1 Testēšanas stratēģija
- **Manuālā testēšana**: Funkcionalitātes testēšana pārlūkā
- **API testēšana**: Galapunktu testēšana ar Postman vai cURL
- **Lietotāja interfeisa testēšana**: Komponentu un skata testēšana

### 7.2 Galvenās testēšanas jomas
1. Lietotāju autentifikācija (reģistrācija, pieteikšanās, izrakstīšanās)
2. Mākslas darbu CRUD operācijas
3. Balsošanas mehānisms
4. Draudzības pieprasījumu plūsma
5. Ziņojumu sūtīšana un saņemšana
6. API atbilžu statusa kodi un formāts

### 7.3 Testēšanas komandas
```powershell
# Backend testi (ja ir konfigurēti)
php artisan test

# Frontend testi
npm run test

# Linting
npm run lint
```

---

## 8. ZINĀMĀS PROBLĒMAS UN TURPMĀKIE UZLABOJUMI

### 8.1 Pašreizējie ierobežojumi
- Reāllaika funkcionalitāte ierobežota (nav WebSocket)
- Attēlu augšupielāde ierobežota ar Base64 kodēšanu
- Nav vairāku valodu atbalsta
- Nav push paziņojumu funkcionalitātes

### 8.2 Turpmākā attīstība
- **WebSocket integrācija**: Reāllaika ziņojumi ar Laravel Echo un Socket.io
- **Attēlu optimizācija**: Servera puses attēlu apstrāde un glabāšana
- **Notifikācijas**: Push notifikācijas jauniem ziņojumiem un draudzības pieprasījumiem
- **Profila pielāgošana**: Lietotāja profila attēli un detalizēta informācija
- **Grupu čats**: Iespēja izveidot grupu sarunas
- **Mākslas darbu kategorijas**: Organizēt mākslas darbus pēc kategorijām vai tagiem
- **Privātuma iestatījumi**: Detalizēti privātuma kontroles mehānismi
- **Administratora panelis**: Satura moderācija un lietotāju pārvaldība

---

## 9. UZTURĒŠANA

### 9.1 Logu pārvaldība
Sistēmas logi atrodas:
- **Backend**: `backend/storage/logs/laravel.log`
- **Frontend**: Pārlūka konsole (Developer Tools)

### 9.2 Datu bāzes dublējumkopijas
```powershell
# SQLite dublējumkopijas izveide
copy backend\database\database.sqlite backend\database\backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sqlite
```

### 9.3 Atjauninājumi
- **Backend atkarības**: `herd composer update`
- **Frontend atkarības**: `npm update`
- **Laravel ietvara atjaunināšana**: Sekot Laravel dokumentācijai

---

## 10. AUTORI UN LICENCE

### 10.1 Projekta autors
Šis projekts tika izveidots kā mācību mērķu demonstrācija.

### 10.2 Izmantotās tehnoloģijas
- Vue.js un Vuetify - MIT Licence
- Laravel - MIT Licence
- Citas bibliotēkas - attiecīgās licences

---

## 11. KONTAKTINFORMĀCIJA UN ATBALSTS

Projekta dokumentācija un kods atrodas projekta direktorijā.

**Projekta atrašanās vieta:** `C:\Users\sarmi\Desktop\ChatApp`

**API dokumentācija:** Pieejama tiešraidē `http://chatapp-api.test/api/health`

---

*Dokumenta versija: 1.0*  
*Pēdējās izmaiņas: 2026. gada 24. februāris*
