<?php
/**
 * FileManager klasė
 * Tvarko šifruotų CSV failų skaitymą, rašymą ir valdymą.
 *
 * CSV struktūra (nešifruotas formatas atmintyje):
 *   name | encrypted_password | url | notes
 */

namespace App\File;

use App\Crypto\CryptoHelper;

class FileManager
{
    private string $filePath;   // Kelias iki vartotojo .csv.enc failo
    private string $fileKey;    // 32 baitų AES raktas (failo lygiui)
    private array  $records;    // Įkelti įrašai (atmintyje)

    // CSV stulpelių indeksai
    private const COL_NAME     = 0;
    private const COL_PASSWORD = 1;
    private const COL_URL      = 2;
    private const COL_NOTES    = 3;

    /**
     * @param string $username  Vartotojo vardas (failo pavadinimui)
     * @param string $fileKey   32 baitų AES raktas
     */
    public function __construct(string $username, string $fileKey)
    {
        $this->filePath = DATA_PATH . '/' . preg_replace('/[^a-zA-Z0-9_]/', '', $username) . '.enc';
        $this->fileKey  = $fileKey;
        $this->records  = [];
    }

    /**
     * Atidaro vartotojo failą:
     *  - Jei failas neegzistuoja – sukuria tuščią
     *  - Jei egzistuoja – iššifruoja ir įkelia į atmintį
     */
    public function open(): void
    {
        if (!file_exists($this->filePath)) {
            // Pirmasis paleidimas: sukuriame tuščią failą
            $this->records = [];
            $this->save();
            return;
        }

        $encoded   = file_get_contents($this->filePath);
        $plaintext = CryptoHelper::decryptFile($encoded, $this->fileKey);
        $this->records = $this->parseCsv($plaintext);
    }

    /**
     * Užšifruoja ir išsaugo failo turinį į diską.
     * Kviečiamas uždarius programą arba po kiekvieno pakeitimo.
     */
    public function save(): void
    {
        if (!is_dir(DATA_PATH)) {
            mkdir(DATA_PATH, 0750, true);
        }

        $csv     = $this->buildCsv($this->records);
        $encoded = CryptoHelper::encryptFile($csv, $this->fileKey);
        file_put_contents($this->filePath, $encoded, LOCK_EX);
    }

    /**
     * Prideda naują slaptažodžio įrašą.
     * Slaptažodis šifruojamas įrašo lygio AES prieš saugojimą.
     *
     * @param string $name      Pavadinimas (unikalus)
     * @param string $password  Slaptažodis (atviras tekstas)
     * @param string $url       URL arba programos pavadinimas
     * @param string $notes     Papildomos pastabos
     * @param string $recordKey Įrašo lygio AES raktas
     * @throws \RuntimeException jei įrašas su tokiu pavadinimu jau egzistuoja
     */
    public function addRecord(
        string $name,
        string $password,
        string $url,
        string $notes,
        string $recordKey
    ): void {
        if ($this->findByName($name) !== null) {
            throw new \RuntimeException("Įrašas {$name} jau egzistuoja.");
        }

        $encPwd = CryptoHelper::encryptPassword($password, $recordKey);

        $this->records[] = [
            self::COL_NAME     => $name,
            self::COL_PASSWORD => $encPwd,
            self::COL_URL      => $url,
            self::COL_NOTES    => $notes,
        ];

        $this->save();
    }

    /**
     * Ieško įrašo pagal pavadinimą (neskiria didžiųjų/mažųjų raidžių).
     * Grąžina tik viešus laukus – slaptažodis NEGRĄŽINAMAS.
     *
     * @param string $name
     * @return array|null  ['name', 'url', 'notes'] arba null
     */
    public function searchRecord(string $name): ?array
    {
        $record = $this->findByName($name);

        if ($record === null) {
            return null;
        }

        return [
            'name'  => $record[self::COL_NAME],
            'url'   => $record[self::COL_URL],
            'notes' => $record[self::COL_NOTES],
        ];
    }

    /**
     * Grąžina iššifruotą slaptažodį (tik pareikalavus).
     *
     * @param string $name
     * @param string $recordKey Įrašo lygio AES raktas
     * @return string|null
     */
    public function revealPassword(string $name, string $recordKey): ?string
    {
        $record = $this->findByName($name);

        if ($record === null) {
            return null;
        }

        return CryptoHelper::decryptPassword($record[self::COL_PASSWORD], $recordKey);
    }

    /**
     * Atnaujina esamą įrašą.
     *
     * @param string      $name       Ieškomas pavadinimas
     * @param string|null $newName    Naujas pavadinimas (arba null – nekeičiamas)
     * @param string|null $newPwd     Naujas slaptažodis (arba null – nekeičiamas)
     * @param string|null $newUrl     Naujas URL
     * @param string|null $newNotes   Naujos pastabos
     * @param string      $recordKey  Įrašo lygio AES raktas
     * @throws \RuntimeException jei įrašas nerastas
     */
    public function updateRecord(
        string  $name,
        ?string $newName,
        ?string $newPwd,
        ?string $newUrl,
        ?string $newNotes,
        string  $recordKey
    ): void {
        $index = $this->findIndexByName($name);

        if ($index === null) {
            throw new \RuntimeException("Įrašas {$name} nerastas.");
        }

        if ($newName !== null && $newName !== $name) {
            if ($this->findByName($newName) !== null) {
                throw new \RuntimeException("Įrašas {$newName} jau egzistuoja.");
            }
            $this->records[$index][self::COL_NAME] = $newName;
        }

        if ($newPwd !== null) {
            $this->records[$index][self::COL_PASSWORD] = CryptoHelper::encryptPassword($newPwd, $recordKey);
        }

        if ($newUrl !== null) {
            $this->records[$index][self::COL_URL] = $newUrl;
        }

        if ($newNotes !== null) {
            $this->records[$index][self::COL_NOTES] = $newNotes;
        }

        $this->save();
    }

    /**
     * Ištrina įrašą pagal pavadinimą.
     *
     * @param string $name
     * @throws \RuntimeException jei įrašas nerastas
     */
    public function deleteRecord(string $name): void
    {
        $index = $this->findIndexByName($name);

        if ($index === null) {
            throw new \RuntimeException("Įrašas {$name} nerastas.");
        }

        array_splice($this->records, $index, 1);
        $this->save();
    }

    /**
     * Grąžina visų įrašų viešuosius duomenis (be slaptažodžių).
     *
     * @return array[]
     */
    public function listRecords(): array
    {
        return array_map(fn($r) => [
            'name'  => $r[self::COL_NAME],
            'url'   => $r[self::COL_URL],
            'notes' => $r[self::COL_NOTES],
        ], $this->records);
    }

    private function findByName(string $name): ?array
    {
        foreach ($this->records as $record) {
            if (\strtolower($record[self::COL_NAME]) === \strtolower($name)) {
                return $record;
            }
        }
        return null;
    }

    private function findIndexByName(string $name): ?int
    {
        foreach ($this->records as $i => $record) {
            if (\strtolower($record[self::COL_NAME]) === \strtolower($name)) {
                return $i;
            }
        }
        return null;
    }

    /**
     * Parso CSV tekstą į masyvą.
     */
    private function parseCsv(string $csv): array
    {
        $records = [];
        $lines   = explode("\n", trim($csv));

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $fields = str_getcsv($line, ',', '"', '\\');
            if (count($fields) === 4) {
                $records[] = $fields;
            }
        }

        return $records;
    }

    /**
     * Konvertuoja masyvą į CSV tekstą.
     */
    private function buildCsv(array $records): string
    {
        $lines = [];

        foreach ($records as $record) {
            $escaped = array_map(function ($field) {
                $field = str_replace('"', '""', $field);
                return '"' . $field . '"';
            }, $record);
            $lines[] = implode(',', $escaped);
        }

        return implode("\n", $lines);
    }
}
