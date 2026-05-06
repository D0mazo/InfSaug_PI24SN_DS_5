<?php
/**
 * Session klasė
 * Sesijų valdymas – prisijungimas, atsijungimas, vartotojo tikrinimas.
 */

namespace App\Auth;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => false, // true kai HTTPS
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function login(string $username, string $salt): void
    {
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['username']  = $username;
        $_SESSION['salt']      = $salt;
        $_SESSION['login_at']  = time();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['username']);
    }

    public static function getUsername(): string
    {
        return $_SESSION['username'] ?? '';
    }

    public static function getSalt(): string
    {
        return $_SESSION['salt'] ?? '';
    }

    /**
     * Reikalauja prisijungimo – jei ne, nukreipia į login puslapį.
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: index.php');
            exit;
        }
    }
}
