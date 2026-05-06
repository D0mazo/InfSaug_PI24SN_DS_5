<?php
/**
 * Pagrindinis įėjimo taškas
 * Rodo prisijungimo/registracijos arba pagrindinės programos HTML.
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Auth\Session;

Session::start();

$loggedIn = Session::isLoggedIn();
$username = $loggedIn ? Session::getUsername() : '';
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureVault – Slaptažodžių tvarkyklė</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php if (!$loggedIn): ?>
<!-- ==================== PRISIJUNGIMO EKRANAS ==================== -->
<div class="auth-screen" id="authScreen">
    <div class="auth-bg">
        <div class="auth-grid"></div>
    </div>

    <div class="auth-card">
        <div class="logo">
            <span class="logo-icon">🔐</span>
            <span class="logo-text">SecureVault</span>
        </div>
        <p class="logo-sub">Saugus slaptažodžių valdymas</p>

        <!-- Tab mygtukai -->
        <div class="auth-tabs">
            <button class="tab-btn active" data-tab="login">Prisijungti</button>
            <button class="tab-btn" data-tab="register">Registruotis</button>
        </div>

        <!-- Prisijungimo forma -->
        <div class="tab-panel active" id="loginPanel">
            <div class="form-group">
                <label>Vartotojo vardas</label>
                <input type="text" id="loginUsername" placeholder="vardas" autocomplete="username">
            </div>
            <div class="form-group">
                <label>Pagrindinis slaptažodis</label>
                <div class="input-eye">
                    <input type="password" id="loginPassword" placeholder="••••••••" autocomplete="current-password">
                    <button class="eye-btn" data-target="loginPassword">👁</button>
                </div>
            </div>
            <button class="btn btn-primary btn-full" id="loginBtn">Prisijungti</button>
        </div>

        <!-- Registracijos forma -->
        <div class="tab-panel" id="registerPanel">
            <div class="form-group">
                <label>Vartotojo vardas</label>
                <input type="text" id="regUsername" placeholder="min. 3 simboliai" autocomplete="username">
            </div>
            <div class="form-group">
                <label>Pagrindinis slaptažodis</label>
                <div class="input-eye">
                    <input type="password" id="regPassword" placeholder="min. 8 simboliai" autocomplete="new-password">
                    <button class="eye-btn" data-target="regPassword">👁</button>
                </div>
            </div>
            <div class="form-group">
                <label>Pakartoti slaptažodį</label>
                <div class="input-eye">
                    <input type="password" id="regPassword2" placeholder="••••••••" autocomplete="new-password">
                    <button class="eye-btn" data-target="regPassword2">👁</button>
                </div>
            </div>
            <button class="btn btn-primary btn-full" id="registerBtn">Registruotis</button>
        </div>

        <div class="auth-message" id="authMessage"></div>
    </div>
</div>

<?php else: ?>
<!-- ==================== PAGRINDINĖ PROGRAMA ==================== -->
<div class="app" id="app">

    <!-- Šoninė juosta -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <span>🔐</span>
            <span>SecureVault</span>
        </div>
        <nav class="sidebar-nav">
            <button class="nav-btn active" data-view="list">
                <span class="nav-icon">📋</span> Visi įrašai
            </button>
            <button class="nav-btn" data-view="add">
                <span class="nav-icon">➕</span> Pridėti
            </button>
            <button class="nav-btn" data-view="search">
                <span class="nav-icon">🔍</span> Paieška
            </button>
            <button class="nav-btn" data-view="generator">
                <span class="nav-icon">⚡</span> Generatorius
            </button>
        </nav>
        <div class="sidebar-footer">
            <span class="user-badge">👤 <?= htmlspecialchars($username) ?></span>
            <button class="btn btn-ghost btn-sm" id="logoutBtn">Atsijungti</button>
        </div>
    </aside>

    <!-- Pagrindinis turinys -->
    <main class="main-content">

        <!-- ---- VISI ĮRAŠAI ---- -->
        <section class="view active" id="viewList">
            <div class="view-header">
                <h2>Visi įrašai</h2>
                <button class="btn btn-primary btn-sm" id="refreshBtn">↻ Atnaujinti</button>
            </div>
            <div class="records-grid" id="recordsGrid">
                <div class="empty-state">Įrašų nėra. Pridėkite pirmąjį!</div>
            </div>
        </section>

        <!-- ---- PRIDĖJIMAS ---- -->
        <section class="view" id="viewAdd">
            <div class="view-header"><h2>Pridėti įrašą</h2></div>
            <div class="form-card">
                <div class="form-group">
                    <label>Pavadinimas *</label>
                    <input type="text" id="addName" placeholder="pvz. Gmail">
                </div>
                <div class="form-group">
                    <label>Slaptažodis *</label>
                    <div class="input-eye">
                        <input type="password" id="addPassword" placeholder="įveskite arba sugeneruokite">
                        <button class="eye-btn" data-target="addPassword">👁</button>
                    </div>
                    <button class="btn btn-ghost btn-sm mt-1" id="fillGeneratedBtn">⚡ Naudoti generatorių</button>
                </div>
                <div class="form-group">
                    <label>URL / Programa</label>
                    <input type="text" id="addUrl" placeholder="https://gmail.com">
                </div>
                <div class="form-group">
                    <label>Pastabos</label>
                    <textarea id="addNotes" rows="3" placeholder="Papildoma informacija..."></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" id="addBtn">💾 Išsaugoti</button>
                    <button class="btn btn-ghost" id="addClearBtn">✕ Valyti</button>
                </div>
                <div class="form-message" id="addMessage"></div>
            </div>
        </section>

        <!-- ---- PAIEŠKA ---- -->
        <section class="view" id="viewSearch">
            <div class="view-header"><h2>Paieška</h2></div>
            <div class="form-card">
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Ieškoti pagal pavadinimą...">
                    <button class="btn btn-primary" id="searchBtn">🔍 Ieškoti</button>
                </div>
                <div id="searchResult" class="search-result hidden"></div>

                <!-- Redagavimo / ištrynimo sekcija -->
                <div id="editSection" class="edit-section hidden">
                    <h3>Redaguoti / Ištrinti</h3>
                    <div class="form-group">
                        <label>Naujas pavadinimas</label>
                        <input type="text" id="editName" placeholder="palikite tuščia – nekeisti">
                    </div>
                    <div class="form-group">
                        <label>Naujas slaptažodis</label>
                        <div class="input-eye">
                            <input type="password" id="editPassword" placeholder="palikite tuščia – nekeisti">
                            <button class="eye-btn" data-target="editPassword">👁</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Naujas URL</label>
                        <input type="text" id="editUrl" placeholder="palikite tuščia – nekeisti">
                    </div>
                    <div class="form-group">
                        <label>Naujos pastabos</label>
                        <textarea id="editNotes" rows="2" placeholder="palikite tuščia – nekeisti"></textarea>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" id="updateBtn">✏️ Atnaujinti</button>
                        <button class="btn btn-danger" id="deleteBtn">🗑 Ištrinti</button>
                    </div>
                    <div class="form-message" id="editMessage"></div>
                </div>
            </div>
        </section>

        <!-- ---- GENERATORIUS ---- -->
        <section class="view" id="viewGenerator">
            <div class="view-header"><h2>Slaptažodžių generatorius</h2></div>
            <div class="form-card generator-card">
                <div class="gen-output">
                    <span id="genResult" class="gen-password">—</span>
                    <button class="btn btn-ghost btn-sm" id="copyGenBtn" title="Kopijuoti">📋</button>
                </div>
                <div class="gen-options">
                    <div class="gen-length">
                        <label>Ilgis: <strong id="lenDisplay">16</strong></label>
                        <input type="range" id="genLength" min="8" max="64" value="16">
                    </div>
                    <label class="checkbox-label">
                        <input type="checkbox" id="genUpper" checked> Didžiosios raidės (A–Z)
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="genNumbers" checked> Skaičiai (0–9)
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="genSymbols" checked> Specialieji simboliai (!@#...)
                    </label>
                </div>
                <button class="btn btn-primary btn-full" id="generateBtn">⚡ Generuoti</button>
                <div class="form-message" id="genMessage"></div>
            </div>
        </section>

    </main>
</div>
<?php endif; ?>

<!-- Pranešimų toast -->
<div class="toast" id="toast"></div>

<script src="assets/js/app.js"></script>
</body>
</html>
