<?php
/**
 * Bootstrap – automatinis klasių įkėlimas ir konfigūracija.
 */

require_once __DIR__ . '/../config/config.php';

// Paprastas PSR-4 stiliaus autoloader
spl_autoload_register(function (string $class): void {
    // Namespace: App\Crypto\CryptoHelper → src/Crypto/CryptoHelper.php
    $prefix = 'App\\';
    $base   = SRC_PATH . '/';

    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $file     = $base . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Klaidos rodymas (kūrimo metu – produkcijai išjungti)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Laiko zona
date_default_timezone_set('Europe/Vilnius');
