/**
 * SecureVault – Pagrindinis JavaScript failas
 * Valdo visą naršyklės pusės logiką: AJAX užklausas, UI būsenas.
 */

'use strict';

const API = 'api.php';

/** Siunčia POST užklausą į API */
async function apiCall(action, data = {}) {
    try {
        const res = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...data }),
        });
        return await res.json();
    } catch (e) {
        return { ok: false, error: 'Tinklo klaida: ' + e.message };
    }
}

/** Rodo toast pranešimą */
let toastTimer;
function showToast(msg, type = 'ok') {
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg;
    t.className   = `toast ${type} show`;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { t.className = 'toast'; }, 3000);
}

/** Nustato formos pranešimą */
function setMsg(elId, msg, type = 'ok') {
    const el = document.getElementById(elId);
    if (!el) return;
    el.textContent  = msg;
    el.className    = `form-message ${type}`;
}

/** Kopijavimas į iškarpinę */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Nukopijuota į iškarpinę!');
    } catch {
        showToast('Nepavyko kopijuoti.', 'err');
    }
}

document.addEventListener('click', e => {
    if (!e.target.classList.contains('eye-btn')) return;
    const targetId = e.target.dataset.target;
    const inp      = document.getElementById(targetId);
    if (!inp) return;
    inp.type       = inp.type === 'password' ? 'text' : 'password';
    e.target.textContent = inp.type === 'password' ? '👁' : '🙈';
});

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab + 'Panel')?.classList.add('active');
        document.getElementById('authMessage').textContent = '';
    });
});

// Prisijungimas
document.getElementById('loginBtn')?.addEventListener('click', async () => {
    const username = document.getElementById('loginUsername')?.value.trim();
    const password = document.getElementById('loginPassword')?.value;
    const msgEl    = document.getElementById('authMessage');

    if (!username || !password) {
        msgEl.textContent = 'Įveskite vartotojo vardą ir slaptažodį.';
        msgEl.className   = 'auth-message err';
        return;
    }

    const btn = document.getElementById('loginBtn');
    btn.textContent = 'Jungiamasi...';
    btn.disabled    = true;

    const res = await apiCall('login', { username, password });

    btn.textContent = 'Prisijungti';
    btn.disabled    = false;

    if (res.ok) {
        window.location.reload();
    } else {
        msgEl.textContent = res.error;
        msgEl.className   = 'auth-message err';
    }
});

// Registracija
document.getElementById('registerBtn')?.addEventListener('click', async () => {
    const username  = document.getElementById('regUsername')?.value.trim();
    const password  = document.getElementById('regPassword')?.value;
    const password2 = document.getElementById('regPassword2')?.value;
    const msgEl     = document.getElementById('authMessage');

    if (!username || !password || !password2) {
        msgEl.textContent = 'Užpildykite visus laukus.';
        msgEl.className   = 'auth-message err';
        return;
    }

    if (password !== password2) {
        msgEl.textContent = 'Slaptažodžiai nesutampa.';
        msgEl.className   = 'auth-message err';
        return;
    }

    const btn = document.getElementById('registerBtn');
    btn.textContent = 'Registruojama...';
    btn.disabled    = true;

    const res = await apiCall('register', { username, password });

    btn.textContent = 'Registruotis';
    btn.disabled    = false;

    msgEl.textContent = res.ok ? res.message : res.error;
    msgEl.className   = `auth-message ${res.ok ? 'ok' : 'err'}`;

    if (res.ok) {
        // Perjungiame į prisijungimo tab
        setTimeout(() => {
            document.querySelector('[data-tab="login"]')?.click();
        }, 1200);
    }
});

// Enter klavišas
['loginUsername', 'loginPassword'].forEach(id => {
    document.getElementById(id)?.addEventListener('keydown', e => {
        if (e.key === 'Enter') document.getElementById('loginBtn')?.click();
    });
});

document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
        btn.classList.add('active');
        const viewId = 'view' + btn.dataset.view.charAt(0).toUpperCase() + btn.dataset.view.slice(1);
        document.getElementById(viewId)?.classList.add('active');

        if (btn.dataset.view === 'list') loadRecords();
    });
});

document.getElementById('logoutBtn')?.addEventListener('click', async () => {
    await apiCall('logout');
    window.location.reload();
});

async function loadRecords() {
    const grid = document.getElementById('recordsGrid');
    if (!grid) return;
    grid.innerHTML = '<div class="empty-state">Kraunama...</div>';

    const res = await apiCall('list');

    if (!res.ok) {
        grid.innerHTML = `<div class="empty-state" style="color:var(--danger)">${res.error}</div>`;
        return;
    }

    if (res.records.length === 0) {
        grid.innerHTML = '<div class="empty-state">Įrašų nėra. Pridėkite pirmąjį!</div>';
        return;
    }

    grid.innerHTML = res.records.map(r => `
        <div class="record-card">
            <div class="record-name">${esc(r.name)}</div>
            <div class="record-url">${r.url ? '🔗 ' + esc(r.url) : '—'}</div>
            <div class="record-notes">${r.notes ? esc(r.notes) : ''}</div>
            <div class="record-actions">
                <button class="btn btn-ghost btn-sm" onclick="revealInCard('${esc(r.name)}', this)">👁 Rodyti</button>
                <button class="btn btn-ghost btn-sm" onclick="goToEdit('${esc(r.name)}')">✏️</button>
                <button class="btn btn-danger btn-sm" onclick="quickDelete('${esc(r.name)}')">🗑</button>
            </div>
            <div class="reveal-area" id="reveal-${esc(r.name)}"></div>
        </div>
    `).join('');
}

function esc(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function revealInCard(name, btn) {
    const area = document.getElementById('reveal-' + name);
    if (!area) return;

    if (area.dataset.shown === '1') {
        area.innerHTML = '';
        area.dataset.shown = '0';
        btn.textContent = '👁 Rodyti';
        return;
    }

    btn.textContent = '...';
    const res = await apiCall('reveal', { name });

    if (!res.ok) { showToast(res.error, 'err'); btn.textContent = '👁 Rodyti'; return; }

    area.innerHTML = `
        <div class="pwd-row" style="margin-top:0.5rem">
            <span style="font-size:0.75rem;color:var(--text-3)">Slaptažodis:</span>
            <code style="font-family:var(--font-mono);color:var(--accent);font-size:0.85rem;flex:1;overflow:hidden;text-overflow:ellipsis">${esc(res.password)}</code>
            <button class="btn btn-ghost btn-sm" onclick="copyToClipboard('${res.password.replace(/'/g,"\\'")}')">📋</button>
        </div>`;
    area.dataset.shown = '1';
    btn.textContent = '🙈 Slėpti';
}

async function quickDelete(name) {
    if (!confirm(`Ištrinti įrašą „${name}"?`)) return;
    const res = await apiCall('delete', { name });
    showToast(res.ok ? res.message : res.error, res.ok ? 'ok' : 'err');
    if (res.ok) loadRecords();
}

function goToEdit(name) {
    // Perjungiame į paieškos rodinį ir užpildome pavadinimą
    document.querySelector('[data-view="search"]')?.click();
    const inp = document.getElementById('searchInput');
    if (inp) { inp.value = name; }
    document.getElementById('searchBtn')?.click();
}

// Pradinis krovimas
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('recordsGrid')) loadRecords();
});

document.getElementById('refreshBtn')?.addEventListener('click', loadRecords);

document.getElementById('addBtn')?.addEventListener('click', async () => {
    const name     = document.getElementById('addName')?.value.trim();
    const password = document.getElementById('addPassword')?.value;
    const url      = document.getElementById('addUrl')?.value.trim();
    const notes    = document.getElementById('addNotes')?.value.trim();

    if (!name)     { setMsg('addMessage', 'Pavadinimas privalomas.', 'err'); return; }
    if (!password) { setMsg('addMessage', 'Slaptažodis privalomas.',  'err'); return; }

    const btn = document.getElementById('addBtn');
    btn.textContent = 'Saugoma...';
    btn.disabled    = true;

    const res = await apiCall('add', { name, password, url: url || '', notes: notes || '' });

    btn.textContent = '💾 Išsaugoti';
    btn.disabled    = false;

    setMsg('addMessage', res.ok ? res.message : res.error, res.ok ? 'ok' : 'err');

    if (res.ok) {
        document.getElementById('addName').value     = '';
        document.getElementById('addPassword').value = '';
        document.getElementById('addUrl').value      = '';
        document.getElementById('addNotes').value    = '';
        showToast(res.message);
    }
});

document.getElementById('addClearBtn')?.addEventListener('click', () => {
    ['addName','addPassword','addUrl','addNotes'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    setMsg('addMessage', '', 'ok');
});

document.getElementById('fillGeneratedBtn')?.addEventListener('click', () => {
    // Navigavimas į generatorių + automatinis pildymas
    const genPwd = document.getElementById('genResult')?.textContent;
    if (genPwd && genPwd !== '—') {
        document.getElementById('addPassword').value = genPwd;
        setMsg('addMessage', 'Sugeneruotas slaptažodis užpildytas.', 'ok');
    } else {
        showToast('Pirmiau sugeneruokite slaptažodį generatoriuje.', 'err');
        document.querySelector('[data-view="generator"]')?.click();
    }
});

let currentSearchName = '';

document.getElementById('searchBtn')?.addEventListener('click', async () => {
    const name = document.getElementById('searchInput')?.value.trim();
    if (!name) { showToast('Įveskite pavadinimą.', 'err'); return; }

    const res = await apiCall('search', { name });

    const resultEl = document.getElementById('searchResult');
    const editSec  = document.getElementById('editSection');

    if (!res.ok) {
        resultEl.innerHTML = `<p style="color:var(--danger)">${res.error}</p>`;
        resultEl.classList.remove('hidden');
        editSec.classList.add('hidden');
        currentSearchName = '';
        return;
    }

    currentSearchName = res.record.name;

    resultEl.innerHTML = `
        <div class="sr-label">Pavadinimas</div>
        <div class="sr-value">${esc(res.record.name)}</div>

        <div class="sr-label">URL / Programa</div>
        <div class="sr-value">${res.record.url ? esc(res.record.url) : '—'}</div>

        <div class="sr-label">Pastabos</div>
        <div class="sr-value">${res.record.notes ? esc(res.record.notes) : '—'}</div>

        <div class="sr-label">Slaptažodis</div>
        <div class="pwd-row">
            <span class="pwd-masked" id="pwdMasked">••••••••••••</span>
            <button class="btn btn-ghost btn-sm" id="revealBtn">👁 Rodyti</button>
            <button class="btn btn-ghost btn-sm" id="copyPwdBtn">📋 Kopijuoti</button>
        </div>
    `;
    resultEl.classList.remove('hidden');
    editSec.classList.remove('hidden');

    // Slaptažodžio atskleidimas
    let revealed = false;
    let revealedPwd = '';

    document.getElementById('revealBtn').addEventListener('click', async function () {
        if (revealed) {
            document.getElementById('pwdMasked').textContent = '••••••••••••';
            this.textContent = '👁 Rodyti';
            revealed = false;
            return;
        }
        this.textContent = '...';
        const r = await apiCall('reveal', { name: currentSearchName });
        if (!r.ok) { showToast(r.error, 'err'); this.textContent = '👁 Rodyti'; return; }
        revealedPwd = r.password;
        document.getElementById('pwdMasked').textContent = r.password;
        document.getElementById('pwdMasked').style.fontFamily = 'var(--font-mono)';
        document.getElementById('pwdMasked').style.letterSpacing = '0';
        this.textContent = '🙈 Slėpti';
        revealed = true;
    });

    document.getElementById('copyPwdBtn').addEventListener('click', async () => {
        if (!revealedPwd) {
            const r = await apiCall('reveal', { name: currentSearchName });
            if (!r.ok) { showToast(r.error, 'err'); return; }
            revealedPwd = r.password;
        }
        copyToClipboard(revealedPwd);
    });
});

// Enter klavišas paieškoje
document.getElementById('searchInput')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('searchBtn')?.click();
});

// Atnaujinimas
document.getElementById('updateBtn')?.addEventListener('click', async () => {
    if (!currentSearchName) { showToast('Pirmiau atlikite paiešką.', 'err'); return; }

    const payload = {
        name:         currentSearchName,
        new_name:     document.getElementById('editName')?.value.trim()     || '',
        new_password: document.getElementById('editPassword')?.value         || '',
        new_url:      document.getElementById('editUrl')?.value.trim()       || '',
        new_notes:    document.getElementById('editNotes')?.value.trim()     ?? '',
    };

    const res = await apiCall('update', payload);
    setMsg('editMessage', res.ok ? res.message : res.error, res.ok ? 'ok' : 'err');
    if (res.ok) {
        showToast(res.message);
        if (payload.new_name) currentSearchName = payload.new_name;
    }
});

// Ištrynimas
document.getElementById('deleteBtn')?.addEventListener('click', async () => {
    if (!currentSearchName) { showToast('Pirmiau atlikite paiešką.', 'err'); return; }
    if (!confirm(`Ištrinti įrašą „${currentSearchName}"?`)) return;

    const res = await apiCall('delete', { name: currentSearchName });
    if (res.ok) {
        showToast(res.message);
        document.getElementById('searchResult').classList.add('hidden');
        document.getElementById('editSection').classList.add('hidden');
        document.getElementById('searchInput').value = '';
        currentSearchName = '';
    } else {
        showToast(res.error, 'err');
    }
});

document.getElementById('genLength')?.addEventListener('input', function () {
    document.getElementById('lenDisplay').textContent = this.value;
});

document.getElementById('generateBtn')?.addEventListener('click', async () => {
    const res = await apiCall('generate', {
        length:    parseInt(document.getElementById('genLength')?.value  || 16),
        uppercase: document.getElementById('genUpper')?.checked    ?? true,
        numbers:   document.getElementById('genNumbers')?.checked   ?? true,
        symbols:   document.getElementById('genSymbols')?.checked   ?? true,
    });

    if (res.ok) {
        document.getElementById('genResult').textContent = res.password;
        setMsg('genMessage', 'Slaptažodis sugeneruotas!', 'ok');
    } else {
        setMsg('genMessage', res.error, 'err');
    }
});

document.getElementById('copyGenBtn')?.addEventListener('click', () => {
    const pwd = document.getElementById('genResult')?.textContent;
    if (pwd && pwd !== '—') copyToClipboard(pwd);
    else showToast('Pirmiau sugeneruokite slaptažodį.', 'err');
});
