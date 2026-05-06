<?php
/**
 * API valdiklis
 * Apdoroja AJAX užklausas iš naršyklės.
 * Grąžina JSON atsakymus.
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Auth\Session;
use App\Auth\AuthManager;
use App\Crypto\CryptoHelper;
use App\File\FileManager;

Session::start();
header('Content-Type: application/json; charset=utf-8');

// Tik POST užklausos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Netinkamas metodas.']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

// ------------------------------------------------------------------ //
//  VIEŠI VEIKSMAI (be prisijungimo)                                   //
// ------------------------------------------------------------------ //

if ($action === 'register') {
    try {
        $auth = new AuthManager();
        $auth->register(trim($input['username'] ?? ''), $input['password'] ?? '');
        echo json_encode(['ok' => true, 'message' => 'Registracija sėkminga! Prisijunkite.']);
    } catch (\Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'login') {
    try {
        $auth     = new AuthManager();
        $userData = $auth->login(trim($input['username'] ?? ''), $input['password'] ?? '');

        Session::login($userData['username'], $userData['salt']);

        // Atidaryti (arba sukurti) vartotojo failą
        $key = CryptoHelper::deriveKey($input['password'], $userData['salt']);
        $fm  = new FileManager($userData['username'], $key);
        $fm->open();

        // Raktą saugome sesijoje (atmintyje, ne diske)
        $_SESSION['file_key'] = base64_encode($key);

        echo json_encode(['ok' => true, 'username' => $userData['username']]);
    } catch (\Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ------------------------------------------------------------------ //
//  APSAUGOTI VEIKSMAI (reikia prisijungimo)                           //
// ------------------------------------------------------------------ //

if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Neprisijungęs.']);
    exit;
}

$username = Session::getUsername();
$fileKey  = base64_decode($_SESSION['file_key'] ?? '');

if (strlen($fileKey) !== AES_KEY_LEN) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Sesija pasibaigė. Prisijunkite iš naujo.']);
    exit;
}

$fm = new FileManager($username, $fileKey);

try {
    $fm->open();
} catch (\Exception $e) {
    echo json_encode(['ok' => false, 'error' => 'Klaida atidarynt failą: ' . $e->getMessage()]);
    exit;
}

switch ($action) {

    // ---- LOGOUT ----
    case 'logout':
        // Failas jau užšifruotas (save() kviečiamas kiekvieno veiksmo metu)
        Session::logout();
        echo json_encode(['ok' => true]);
        break;

    // ---- SĄRAŠAS ----
    case 'list':
        echo json_encode(['ok' => true, 'records' => $fm->listRecords()]);
        break;

    // ---- PAIEŠKA ----
    case 'search':
        $result = $fm->searchRecord($input['name'] ?? '');
        if ($result === null) {
            echo json_encode(['ok' => false, 'error' => 'Įrašas nerastas.']);
        } else {
            echo json_encode(['ok' => true, 'record' => $result]);
        }
        break;

    // ---- SLAPTAŽODŽIO ATSKLEIDIMAS ----
    case 'reveal':
        $pwd = $fm->revealPassword($input['name'] ?? '', $fileKey);
        if ($pwd === null) {
            echo json_encode(['ok' => false, 'error' => 'Įrašas nerastas.']);
        } else {
            echo json_encode(['ok' => true, 'password' => $pwd]);
        }
        break;

    // ---- PRIDĖJIMAS ----
    case 'add':
        try {
            $fm->addRecord(
                trim($input['name']     ?? ''),
                $input['password']      ?? '',
                trim($input['url']      ?? ''),
                trim($input['notes']    ?? ''),
                $fileKey
            );
            echo json_encode(['ok' => true, 'message' => 'Įrašas pridėtas sėkmingai.']);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ---- ATNAUJINIMAS ----
    case 'update':
        try {
            $fm->updateRecord(
                trim($input['name']         ?? ''),
                isset($input['new_name'])    && $input['new_name']    !== '' ? trim($input['new_name'])    : null,
                isset($input['new_password'])&& $input['new_password']!== '' ? $input['new_password']     : null,
                isset($input['new_url'])     && $input['new_url']     !== '' ? trim($input['new_url'])     : null,
                isset($input['new_notes'])   ? trim($input['new_notes']) : null,
                $fileKey
            );
            echo json_encode(['ok' => true, 'message' => 'Įrašas atnaujintas sėkmingai.']);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ---- IŠTRYNIMAS ----
    case 'delete':
        try {
            $fm->deleteRecord(trim($input['name'] ?? ''));
            echo json_encode(['ok' => true, 'message' => 'Įrašas ištrintas.']);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ---- SLAPTAŽODŽIO GENERATORIUS ----
    case 'generate':
        $pwd = CryptoHelper::generatePassword(
            (int)  ($input['length']    ?? DEFAULT_PWD_LENGTH),
            (bool) ($input['uppercase'] ?? true),
            (bool) ($input['numbers']   ?? true),
            (bool) ($input['symbols']   ?? true)
        );
        echo json_encode(['ok' => true, 'password' => $pwd]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Nežinomas veiksmas.']);
}
