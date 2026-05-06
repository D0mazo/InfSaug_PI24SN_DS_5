<?php
/**
 * AuthManager klasė
 * Vartotojų registracija ir prisijungimas su Argon2id maiša.
 * Kiekvienas vartotojas turi atskirą šifruotą duomenų failą.
 *
 * Vartotojų saugojimo formatas (users.json):
 * {
 *   "username": {
 *     "hash":     "<argon2id hash>",
 *     "salt":     "<hex salt PBKDF2 raktui>",
 *     "created":  "<ISO timestamp>"
 *   }
 * }
 */

namespace App\Auth;

use App\Crypto\CryptoHelper;

class AuthManager
{
    private string $usersFile;

    public function __construct()
    {
        $this->usersFile = DATA_PATH . '/users.json';
    }

    // ------------------------------------------------------------------ //
    //  REGISTRACIJA                                                        //
    // ------------------------------------------------------------------ //

    /**
     * Registruoja naują vartotoją.
     *
     * @param string $username  Vartotojo vardas
     * @param string $password  Pagrindinis slaptažodis (atviras tekstas)
     * @throws \RuntimeException jei vartotojas jau egzistuoja
     */
    public function register(string $username, string $password): void
    {
        $this->validateCredentials($username, $password);

        $users = $this->loadUsers();

        $key = strtolower($username);
        if (isset($users[$key])) {
            throw new \RuntimeException("Vartotojas {$username} jau registruotas.");
        }

        // Argon2id maiša
        $hash = password_hash($password, HASH_ALGO, HASH_OPTIONS);

        // Atskiras salt PBKDF2 raktui (failo šifravimui)
        $salt = CryptoHelper::generateSalt();

        $users[$key] = [
            'username' => $username,
            'hash'     => $hash,
            'salt'     => $salt,
            'created'  => date('c'),
        ];

        $this->saveUsers($users);
    }

    // ------------------------------------------------------------------ //
    //  PRISIJUNGIMAS                                                       //
    // ------------------------------------------------------------------ //

    /**
     * Patikrina prisijungimo duomenis.
     *
     * @param string $username
     * @param string $password
     * @return array  ['username', 'salt'] sesijos duomenims
     * @throws \RuntimeException jei neteisingi duomenys
     */
    public function login(string $username, string $password): array
    {
        $users = $this->loadUsers();
        $key   = strtolower($username);

        if (!isset($users[$key])) {
            // Vienodas klaidos pranešimas saugumo sumetimais
            throw new \RuntimeException("Neteisingas vartotojo vardas arba slaptažodis.");
        }

        $userData = $users[$key];

        if (!password_verify($password, $userData['hash'])) {
            throw new \RuntimeException("Neteisingas vartotojo vardas arba slaptažodis.");
        }

        // Jei reikia – atnaujinama maiša (pvz., pakeitus HASH_OPTIONS)
        if (password_needs_rehash($userData['hash'], HASH_ALGO, HASH_OPTIONS)) {
            $users[$key]['hash'] = password_hash($password, HASH_ALGO, HASH_OPTIONS);
            $this->saveUsers($users);
        }

        return [
            'username' => $userData['username'],
            'salt'     => $userData['salt'],
        ];
    }

    // ------------------------------------------------------------------ //
    //  VIDINIAI METODAI                                                    //
    // ------------------------------------------------------------------ //

    private function loadUsers(): array
    {
        if (!file_exists($this->usersFile)) {
            return [];
        }

        $content = file_get_contents($this->usersFile);
        return json_decode($content, true) ?? [];
    }

    private function saveUsers(array $users): void
    {
        if (!is_dir(DATA_PATH)) {
            mkdir(DATA_PATH, 0750, true);
        }

        file_put_contents(
            $this->usersFile,
            json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function validateCredentials(string $username, string $password): void
    {
        if (strlen($username) < 3 || strlen($username) > 50) {
            throw new \InvalidArgumentException("Vartotojo vardas turi būti 3–50 simbolių.");
        }

        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
            throw new \InvalidArgumentException("Vartotojo vardas gali turėti tik raides, skaičius, _ ir -.");
        }

        if (strlen($password) < 8) {
            throw new \InvalidArgumentException("Slaptažodis turi būti bent 8 simbolių.");
        }
    }
}
