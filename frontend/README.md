# DoodleVerse frontend

Frontend ir veidots ar Vue 3 un Vuetify 3. Tas nodrošina lietotāja saskarni zīmēšanai, galerijai, tērzēšanai, draugu pārvaldībai un iestatījumiem.

## Tehnoloģijas

- Vue 3
- Vuetify 3
- Vite
- Vue Router
- Axios
- Laravel Echo

## Startēšana lokāli

```bash
npm install
npm run dev
```

Tipiska .env konfigurācija:

```env
VITE_API_URL=http://localhost:8000/api
VITE_REVERB_APP_KEY=your_reverb_key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

## Mapju pārskats

- src/views: lapas skati
- src/components: atkārtoti izmantojami UI bloki
- src/composables: koplietojama loģika, piemēram, i18n
- src/services: API klients
- src/router: maršruti un sargi
- src/plugins: Vuetify un Echo inicializācija
- src/utils: palīgfunkcijas, piemēram, renderPaths
- src/styles: globālie stili

## Galvenie skati

- Auth.vue: pieteikšanās un reģistrācija
- Home.vue: sākumlapa ar aktivitātes pārskatu
- Draw.vue: pilnais zīmēšanas redaktors
- Gallery.vue: zīmējumu saraksts, filtrēšana, balsošana, komentāri
- Messages.vue un Chat.vue: ziņojumi un sarunas
- Friends.vue: draugu pieprasījumi un saraksts
- Settings.vue: profils, pieejamība, avatāra redaktors
- Admin.vue: administrēšanas panelis

## Datu plūsma starp frontend un backend

- Frontend saņem un nosūta datus tikai caur backend API.
- Autentifikācija notiek ar Sanctum tokenu, kas tiek pievienots Authorization galvenē.
- Reāllaika ziņojumi tiek saņemti caur Reverb kanāliem.
- Zīmējuma dati tiek glabāti kā JSON ceļi un renderēti ar utils/renderPaths.js.

## Būvēšana produkcijai

```bash
npm run build
npm run preview
```
