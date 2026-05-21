# Frontend — ChatApp

Lietotāja saskarne ir veidota ar Vue 3, Vuetify 3 un Vite. Visi stili ir iekļauti tieši komponentu failos kā `<style scoped>` bloki.

---

## Galvenā struktūra

### `src/main.js`

Lietojumprogrammas ieejas punkts. Inicializē Vue lietotni, pievieno Vuetify spraudni, konfigurē maršrutētāju un pievieno globālos stilus (`main.css`, `global.css`).

### `src/App.vue`

Saknes komponents. Satur `<router-view>` un parāda globālos elementus — galveni un kājeni — ja lietotājs ir pierakstījies.

---

## Skati (`src/views/`)

### `Auth.vue`

Autentifikācijas skats. Satur gan pieteikšanās, gan reģistrācijas formu vienā lapā. Pārslēdzas starp abiem režīmiem. Atbalsta Google OAuth pieteikšanos.

### `Home.vue`

Sākumlapa pēc pieteikšanās. Parāda jaunākos un populārākos zīmējumus, pašreizējo nedēļas tēmu un saīsnes uz galvenajām lapām.

### `Chat.vue`

Galvenais sarakstes skats. Satur sarunu sarakstu kreisajā pusē un atvērtās sarunas ziņojumus labajā pusē. Izmanto Laravel Reverb reāllaikā, lai parādītu jaunas ziņas.

### `Friends.vue`

Draugu pārvaldības lapa. Ļauj meklēt lietotājus, sūtīt draudzības pieprasījumus, apstiprināt vai noraidīt ienākošos pieprasījumus, kā arī noņemt draugus.

### `Gallery.vue`

Zīmējumu galerija. Parāda publiskos zīmējumus ar filtrēšanu pēc tēmas un meklēšanu pēc nosaukuma vai autora. Satur detalizētu skatu ar komentāriem un balsošanu. Ietver arī arhīva sadaļu ar iepriekšējo nedēļu tēmām.

### `Draw.vue`

Zīmēšanas audekls. Ļauj zīmēt ar dažādiem instrumentiem — zīmulis, dzēšgumija, formas (taisnstūris, ovāls, līnija), teksts, krāsas kauss. Var saglabāt zīmējumu galerijā vai sarunā. Pilnībā pielāgots mobilajām ierīcēm ar apakšējo dokstaciju un slīdošo sānjoslu.

### `Messages.vue`

Tiešo ziņojumu saraksts. Parāda visas privātās sarunas ar meklēšanas iespēju.

### `Profile.vue`

Lietotāja profila lapa. Parāda lietotāja zīmējumus, draugu skaitu un informāciju. Ļauj rediģēt savu profilu (vārds, bilde).

### `Settings.vue`

Iestatījumu lapa. Ļauj mainīt valodu (latviešu/angļu), krāsu shēmu un citus personīgos iestatījumus.

### `Admin.vue`

Administratora panelis. Pieejams tikai lietotājiem ar `is_admin = true`. Ļauj pārvaldīt lietotājus, ziņojumus, zīmējumus, sarunas un nedēļas tēmas.

### `Invite.vue`

Grupas uzaicinājumu lapa. Apstrādā ielūguma saites uz grupas sarunām.

---

## Komponenti (`src/components/`)

### `AppHeader/AppHeader.vue`

Lapas galvene. Satur navigācijas saites, lietotāja ikonas pogu un valodas/tēmas pārslēgu.

### `AppFooter/AppFooter.vue`

Lapas kājene. Parāda autortiesību informāciju un saiti uz projektu.

### `ChatBox/ChatBox.vue`

Pilnfunkcionāls tērzēšanas lodziņš. Parāda ziņojumus, ļauj sūtīt tekstu, zīmējumus un attēlus. Atbalsta grupas kanālus.

### `ChatPreview/ChatPreview.vue`

Sarunas priekšskatījuma rinda sarunu sarakstā. Parāda pēdējo ziņu, laiku un nelasīto ziņu skaitu.

### `DrawingCard/DrawingCard.vue`

Karte zīmējuma attēlošanai galerijā. Parāda miniatūru, autoru, nosaukumu un balsu skaitu.

### `DrawDialog/DrawDialog.vue`

Dialogs zīmēšanas audekla atvēršanai no sarunas. Ļauj nosūtīt zīmējumu tieši ziņojumā.

### `GalleryGrid/GalleryGrid.vue`

Galvenā galerijas komponente. Renderē zīmējumu karšu tīklojumu ar lappušu maiņu.

### `EmptyState/EmptyState.vue`

Tukšā stāvokļa komponents. Parādās, kad nav datu ko attēlot — piemēram, nav draugu vai ziņojumu.

### `LoadingSpinner/LoadingSpinner.vue`

Ielādes indikatora komponents. Rotējoša ikona, kas parādās datu ielādes laikā.

### `AccessibilityHelp/AccessibilityHelp.vue`

Pieejamības palīdzības komponents. Sniedz tastatūras navigācijas norādījumus.

---

## Veidojumi (`src/composables/`)

### `useI18n.js`

Tulkošanas sistēma. Satur latviešu un angļu valodas vārdnīcas. Eksportē funkciju `t(key)` teksta tulkošanai pēc atslēgas. Valoda tiek saglabāta `localStorage` kā `app_language`.

### `useErrorHandler.js`

Kļūdu apstrādes palīgfunkcija. Pārvērš API kļūdas lasāmos paziņojumos.

---

## Pakalpojumi (`src/services/`)

### `api.js`

Axios konfigurācija. Iestata pamata URL, pievieno autentifikācijas galveni (`Bearer token`) no `localStorage`, apstrādā 401 kļūdas (automātiska izrakstīšanās).

---

## Maršrutētājs (`src/router/`)

### `index.js`

Vue Router konfigurācija. Definē visus maršrutus un navigācijas sargus — nepierakstīts lietotājs tiek novirzīts uz `/auth`.

---

## Spraudņi (`src/plugins/`)

### `vuetify.js`

Vuetify 3 inicializācija ar pielāgotu tumšo tēmu un MDI ikonu komplektu.

### `echo.js`

Laravel Echo inicializācija ar Reverb savienojumu reāllaika apraides klausīšanai.

---

## Utilītas (`src/utils/`)

### `renderPaths.js`

Palīgfunkcija zīmēšanas ceļu renderēšanai uz HTML `<canvas>` elementa. Izmanto `Draw.vue` un `DrawingCard.vue` zīmējumu priekšskatījumu ģenerēšanai.

---

## Globālie stili (`src/styles/`)

### `main.css`

CSS mainīgie (krāsas, apaļojumi, ēnas), fonta importi un pamata elementu stili.

### `global.css`

Papildu globālie stili un Vuetify komponentu pārrakstīšana.

---

## Konfigurācija

- `vite.config.js` — Vite konfigurācija ar Vue spraudni un ceļu aliasiem (`@` -> `src/`)
- `package.json` — Projekta atkarības un npm skripti (`dev`, `build`, `preview`)
- `index.html` — HTML ieejas fails ar Vue lietotnes piesaistes punktu
