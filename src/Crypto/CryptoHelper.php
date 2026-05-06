<?php
/**
 * Crypto klasė
 * Atsakinga už AES šifravimą/iššifravimą failo lygyje ir įrašų lygyje.
 */

namespace App\Crypto;

class CryptoHelper
{

    /**
     * Užšifruoja failo turinį AES-256-CBC algoritmu.
     * Grąžina Base64 koduotą eilutę: IV + ciphertext.
     *
     * @param string $plaintext  Originalus tekstas
     * @param string $key        32 baitų raktų eilutė
     * @return string
     */
    public static function encryptFile(string $plaintext, string $key): string
    {
        $iv         = random_bytes(AES_IV_LEN);
        $ciphertext = openssl_encrypt($plaintext, AES_CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('Failo šifravimas nepavyko: ' . openssl_error_string());
        }

        // Saugome: base64(IV + ciphertext)
        return base64_encode($iv . $ciphertext);
    }

    /**
     * Iššifruoja failo turinį.
     *
     * @param string $encoded  Base64 koduotas IV + ciphertext
     * @param string $key      32 baitų raktų eilutė
     * @return string
     */
    public static function decryptFile(string $encoded, string $key): string
    {
        $raw        = base64_decode($encoded, true);

        if ($raw === false || strlen($raw) < AES_IV_LEN) {
            throw new \RuntimeException('Netinkamas failo formatas iššifravimui.');
        }

        $iv         = substr($raw, 0, AES_IV_LEN);
        $ciphertext = substr($raw, AES_IV_LEN);
        $plaintext  = openssl_decrypt($ciphertext, AES_CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($plaintext === false) {
            throw new \RuntimeException('Failo iššifravimas nepavyko. Neteisingas raktas arba sugadintas failas.');
        }

        return $plaintext;
    }

    /**
     * Užšifruoja vieną slaptažodžio reikšmę.
     * Grąžina Base64: IV + ciphertext.
     *
     * @param string $password   Slaptažodis
     * @param string $key        32 baitų raktų eilutė
     * @return string
     */
    public static function encryptPassword(string $password, string $key): string
    {
        $iv         = random_bytes(AES_IV_LEN);
        $ciphertext = openssl_encrypt($password, RECORD_CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('Slaptažodžio šifravimas nepavyko.');
        }

        return base64_encode($iv . $ciphertext);
    }

    /**
     * Iššifruoja vieną slaptažodžio reikšmę.
     *
     * @param string $encoded  Base64 koduotas IV + ciphertext
     * @param string $key      32 baitų raktų eilutė
     * @return string
     */
    public static function decryptPassword(string $encoded, string $key): string
    {
        $raw = base64_decode($encoded, true);

        if ($raw === false || strlen($raw) < AES_IV_LEN) {
            throw new \RuntimeException('Netinkamas šifruoto slaptažodžio formatas.');
        }

        $iv         = substr($raw, 0, AES_IV_LEN);
        $ciphertext = substr($raw, AES_IV_LEN);
        $plaintext  = openssl_decrypt($ciphertext, RECORD_CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($plaintext === false) {
            throw new \RuntimeException('Slaptažodžio iššifravimas nepavyko.');
        }

        return $plaintext;
    }


    /**
     * Generuoja 32 baitų AES raktą iš paprasto teksto naudojant PBKDF2.
     *
     * @param string $password  Vartotojo įvestas pagrindinis slaptažodis
     * @param string $salt      Hex koduotas salt
     * @return string           32 baitų raktas
     */
    public static function deriveKey(string $password, string $salt): string
    {
        return hash_pbkdf2('sha256', $password, hex2bin($salt), 100_000, AES_KEY_LEN, true);
    }

    /**
     * Generuoja naują atsitiktinį salt (32 baitai → 64 hex simboliai).
     *
     * @return string
     */
    public static function generateSalt(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Sugeneruoja kriptografiškai saugų atsitiktinį slaptažodį.
     *
     * @param int  $length      Ilgis (numatytasis 16)
     * @param bool $uppercase   Didžiosios raidės
     * @param bool $numbers     Skaičiai
     * @param bool $symbols     Specialieji simboliai
     * @return string
     */
    public static function generatePassword(
        int  $length    = DEFAULT_PWD_LENGTH,
        bool $uppercase = true,
        bool $numbers   = true,
        bool $symbols   = true
    ): string {
        $lower   = 'abcdefghijklmnopqrstuvwxyz';
        $upper   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $nums    = '0123456789';
        $special = '!@#$%^&*()-_=+[]{}|;:,.<>?';

        $charset  = $lower;
        $required = [];

        // Garantuojame bent vieną simbolį iš kiekvienos grupės
        $required[] = $lower[random_int(0, strlen($lower) - 1)];

        if ($uppercase) {
            $charset   .= $upper;
            $required[] = $upper[random_int(0, strlen($upper) - 1)];
        }
        if ($numbers) {
            $charset   .= $nums;
            $required[] = $nums[random_int(0, strlen($nums) - 1)];
        }
        if ($symbols) {
            $charset   .= $special;
            $required[] = $special[random_int(0, strlen($special) - 1)];
        }

        $password = $required;
        $charLen  = strlen($charset);

        for ($i = count($required); $i < $length; $i++) {
            $password[] = $charset[random_int(0, $charLen - 1)];
        }

        // Fisher-Yates maišymas
        for ($i = count($password) - 1; $i > 0; $i--) {
            $j              = random_int(0, $i);
            [$password[$i], $password[$j]] = [$password[$j], $password[$i]];
        }

        return implode('', $password);
    }
}
