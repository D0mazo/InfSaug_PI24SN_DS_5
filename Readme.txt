# SecureVault – Projekto struktūra

## Failai ir jų paskirtis

### `config/config.php`
- Visos konstantos vienoje vietoje (algoritmai, keliai, sesijos nustatymai)
- AES-256-CBC šifro nustatymai, PBKDF2 iteracijos, Argon2id parametrai

### `src/bootstrap.php`
- Automatinis klasių įkėlimas (autoloader)
- Įkelia `config.php`, nustato laiko zoną

### `src/Crypto/CryptoHelper.php`
- `encryptFile()` / `decryptFile()` – AES-256-CBC failo lygio šifravimas/iššifravimas
- `encryptPassword()` / `decryptPassword()` – AES-256-CBC individualių slaptažodžių šifravimas
- `deriveKey()` – PBKDF2 rakto išvedimas iš pagrindinio slaptažodžio (100 000 iter.)
- `generateSalt()` – atsitiktinis 32 baitų salt
- `generatePassword()` – kriptografiškai saugus slaptažodžių generatorius

### `src/File/FileManager.php`
- `open()` – atidaro vartotojo `.enc` failą arba sukuria naują (pirmas paleidimas)
- `save()` – užšifruoja ir rašo CSV į diską (kviečiama po kiekvieno pakeitimo ir atsijungus)
- `addRecord()` – prideda įrašą, slaptažodį šifruoja prieš saugojimą
- `searchRecord()` – grąžina pavadinimą, URL, pastabas – **slaptažodžio nerodo**
- `revealPassword()` – iššifruoja slaptažodį tik pareikalavus
- `updateRecord()` – atnaujina laukus, slaptažodį iš naujo šifruoja
- `deleteRecord()` – šalina įrašą ir išsaugo failą
- `listRecords()` – sąrašas be slaptažodžių

### `src/Auth/AuthManager.php`
- `register()` – kuria vartotoją, slaptažodį maišo su **Argon2id**
- `login()` – tikrina maišą, grąžina `username` ir `salt` sesijos raktui

### `src/Auth/Session.php`
- `start()` / `login()` / `logout()` – PHP sesijų valdymas
- `requireLogin()` – nukreipia į login jei neprisijungęs
- Sesijoje saugomas **tik** base64 AES raktas – niekada nerašomas į diską

### `public/index.php`
- Pagrindinis puslapis
- Jei neprisijungęs → rodo login/registracijos formą
- Jei prisijungęs → rodo pilną programos UI

### `public/api.php`
- Priima AJAX POST užklausas JSON formatu
- Veiksmai: `register`, `login`, `logout`, `list`, `search`, `reveal`, `add`, `update`, `delete`, `generate`
- Apsaugoti veiksmai tikrina sesiją prieš vykdymą

### `public/assets/css/style.css`
- Visas UI dizainas

### `public/assets/js/app.js`
- Visa naršyklės logika: formos, AJAX, navigacija, toast pranešimai
- Slaptažodžio rodymas/slėpimas, kopijavimas į iškarpinę

### `data/` (sukuriama automatiškai)
- `users.json` – vartotojų Argon2id maišos ir PBKDF2 salt'ai
- `<vardas>.enc` – kiekvieno vartotojo šifruotas CSV failas