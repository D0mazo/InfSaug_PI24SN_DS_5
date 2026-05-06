<?php
/**
 * Konfigūracijos failas
 * Password Manager v1.0
 */

define('APP_NAME', 'SecureVault');
define('APP_VERSION', '1.0.0');

// Keliai
define('ROOT_PATH', dirname(__DIR__));
define('DATA_PATH', ROOT_PATH . '/data');
define('SRC_PATH',  ROOT_PATH . '/src');

// Sesijos nustatymai
define('SESSION_NAME', 'securevault_sess');
define('SESSION_LIFETIME', 3600); // 1 valanda

// AES šifravimas (failo lygio)
define('AES_CIPHER',   'AES-256-CBC');
define('AES_KEY_LEN',  32);
define('AES_IV_LEN',   16);

// Slaptažodžių šifravimas (įrašo lygio)
define('RECORD_CIPHER', 'AES-256-CBC'); // galima pakeisti į DES-EDE3-CBC

// Maišos nustatymai (vartotojų slaptažodžiams)
define('HASH_ALGO',    PASSWORD_ARGON2ID);
define('HASH_OPTIONS', [
    'memory_cost' => 65536,
    'time_cost'   => 4,
    'threads'     => 2,
]);


define('DEFAULT_PWD_LENGTH', 16);
