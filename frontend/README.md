# DoodleVerse frontend

Frontend ir veidots ar Vue 3 un Vuetify 3. Tas ir lietotāja saskarnes slānis, kas atbild par navigāciju, zīmēšanu, galeriju, sarunām, draugiem, profilu, iestatījumiem un administratora paneli.

Lietotne darbojas kā vienas lapas risinājums, tāpēc skati tiek pārslēgti bez pilnas lapas pārlādes, un visi dati tiek pieprasīti no backend API.

## Tehnoloģiju steks

- Vue 3
- Vuetify 3
- Vite
- Vue Router
- Axios
- Laravel Echo

## Frontend loma projektā

Frontend ir atbildīgs par:

- lapu un sadaļu navigāciju;
- formu ievadi un datu nosūtīšanu uz API;
- zīmēšanas audekla un tā rīku attēlošanu;
- galerijas, komentāru un balsojumu vizualizāciju;
- sarunu un ziņojumu attēlošanu;
- profila un avatāra rediģēšanu;
- lokālo iestatījumu un valodas saglabāšanu;
- reāllaika notikumu saņemšanu.

Praktiski tas nozīmē, ka visas lietotāja darbības, sākot no pieteikšanās līdz zīmējuma saglabāšanai, iziet caur vienu saskarni. Frontend arī paslēpj API detaļas no lietotāja un pārvērš servera atbildes saprotamos skatos, kartēs, dialogos un formulās.

## Startēšana lokāli

```bash
npm install
npm run dev
```

Tipiska `.env` konfigurācija:

```env
VITE_API_URL=http://localhost:8000/api
VITE_REVERB_APP_KEY=your_reverb_key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

Frontend izmanto šos mainīgos, lai zinātu, kur atrodas backend API un Reverb serveris.

## Mapju pārskats

- `src/views` — lapu skati
- `src/components` — atkārtoti izmantojami lietotāja saskarnes komponenti
- `src/composables` — koplietojama loģika, piemēram, tulkojumi
- `src/services` — API klients
- `src/router` — maršruti un sargi
- `src/plugins` — Vuetify un Echo inicializācija
- `src/utils` — palīgfunkcijas, piemēram, zīmējumu renderēšana
- `src/styles` — globālie stili

## Galvenie skati

### Autentifikācija un pamatskati

- `Auth.vue` — pieteikšanās un reģistrācija
- `Home.vue` — sākumlapa ar projekta kopsavilkumu un īsceļiem
- `Settings.vue` — profils, paziņojumi, valoda un avatāra redaktors
- `Profile.vue` — publiskais profils ar lietotāja darbiem
- `Admin.vue` — administrēšanas panelis

### Zīmēšana un galerija

- `Draw.vue` — pilnais zīmēšanas redaktors
- `Gallery.vue` — zīmējumu saraksts, meklēšana, balsošana un komentāri
- `Invite.vue` — grupu ielūgumu pieņemšana

### Saziņa

- `Chat.vue` — galvenais sarunu skats
- `Messages.vue` — tiešo sarunu pārskats
- `Friends.vue` — draugu sistēma

## Galvenie komponenti

### Atkārtoti lietojami bloki

- `AppHeader/AppHeader.vue` — galvene ar navigāciju un lietotāja izvēlnēm
- `AppFooter/AppFooter.vue` — kājene
- `EmptyState/EmptyState.vue` — tukša stāvokļa attēlojums
- `LoadingSpinner/LoadingSpinner.vue` — ielādes indikators
- `AccessibilityHelp/AccessibilityHelp.vue` — pieejamības norādes

### Zīmēšanas un galerijas komponenti

- `DrawDialog/DrawDialog.vue` — zīmēšanas dialogs sarunām
- `DrawingCard/DrawingCard.vue` — zīmējuma karte galerijā
- `GalleryGrid/GalleryGrid.vue` — galerijas tīklojuma komponente
- `ChatBox/ChatBox.vue` — sarunu lodziņš ar tekstu un zīmējumiem
- `ChatPreview/ChatPreview.vue` — sarunas priekšskatījums

## Kompozīcijas funkcijas

- `useI18n.js` — tulkojumi latviešu un angļu valodai
- `useErrorHandler.js` — API kļūdu pārvēršana saprotamos paziņojumos

`useI18n.js` ir svarīgs visai lietotnei, jo tas nosaka, kā tiek rādīti teksti, pogu nosaukumi un kļūdu paziņojumi.

## Pakalpojumi

- `api.js` — Axios konfigurācija ar pamata URL un autorizācijas galveni

Šis klients automātiski izmanto tokenu no `localStorage` un vienā vietā centralizē API kļūdu apstrādi.

## Router un spraudņi

- `router/index.js` — maršruti un sargi
- `plugins/vuetify.js` — Vuetify inicializācija
- `plugins/echo.js` — Laravel Echo inicializācija

Router nosaka, kurš skats tiek atvērts, bet Echo ļauj saņemt reāllaika notikumus no backend.

## Utilītas

- `renderPaths.js` — zīmēšanas datu attēlošana canvas elementā
- `avatarZoom.js` — avatāra palielināšanas dialogs

`renderPaths.js` ir centrālā vieta, kur saglabātie zīmēšanas ceļi tiek pārvērsti redzamā attēlā galerijā, priekšskatījumos un sarunu satura blokos.

## Globālie stili

- `main.css` — bāzes vizuālie mainīgie un vispārīgie stili
- `global.css` — papildu globālie stili un Vuetify pārrakstījumi

Šie faili nosaka projekta krāsu paleti, noapaļojumus, ēnas un kopējo vizuālo valodu.

## Datu plūsma frontend pusē

1. Skats tiek ielādēts un pieprasa datus no backend API.
2. Atbilde tiek saglabāta Vue stāvoklī.
3. Komponents uzzīmē sarakstu, formu vai audeklu.
4. Lietotāja darbība nosūta izmaiņas atpakaļ uz backend.
5. Pēc veiksmīga pieprasījuma frontend vai nu atjauno lokālo stāvokli, vai atkārtoti ielādē datus.

Tipiska plūsma ir, piemēram, šāda:

- lietotājs atver Gallery skatu;
- frontend pieprasa zīmējumus no API;
- saraksts tiek attēlots kartēs ar priekšskatījumu un balsošanas pogām;
- lietotājs atver detaļas, pievieno komentāru vai nobalso;
- frontend nosūta pieprasījumu uz backend un pēc atbildes atjauno attēlojumu.

Tas pats princips attiecas uz Draw un Chat skatiem, kur audekla dati vai ziņojumi tiek izgūti, parādīti un pēc tam atkal saglabāti serverī.

## Darba principi

- Zīmējumi tiek glabāti kā JSON ceļi, nevis kā vienkārši statiski attēli.
- Galerijā un sarunās tiek izmantoti tie paši renderēšanas principi, lai priekšskatījumi būtu vienādi.
- Atkārtoti lietojamie komponenti samazina dublēšanos un atvieglo uzturēšanu.
- Mobilā versija tiek pielāgota atsevišķi, jo zīmēšanas un galerijas ekrāni ir intensīvi izmantoti telefonos.

Sistēmas uzturēšanai ir svarīgi, lai jauns UI elements vienmēr izmantotu esošos komponentus un esošo API slāni, nevis rakstītu savus atsevišķus pieprasījumus. Tas palīdz saglabāt vienotu stilu un novērš dublētu loģiku.

## Būvēšana produkcijai

```bash
npm run build
npm run preview
```
