# Suivi Kilométrique Fiat 500e — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a PHP + vanilla JS web app to track km vs LOA contract, deployable on Synology NAS (Web Station + PHP), accessible depuis smartphone/tablette sur le réseau local.

**Architecture:** `index.html` (SPA vanilla JS) appelle `api.php` (API REST PHP) qui lit/écrit `data/data.json`. Aucune dépendance externe, aucun build. Déploiement = copie des fichiers sur le NAS (lecteur Y:\).

**Tech Stack:** PHP 7.4+, HTML5, CSS3, vanilla JS (ES6+). Stockage : JSON file.

---

## File Map

| Fichier | Rôle |
|---------|------|
| `index.html` | SPA complète — interface tabs, dashboard, saisie, historique, paramètres |
| `api.php` | API REST PHP — 5 actions : config, update_config, entries, add_entry, delete_entry |
| `data/data.json` | Stockage (créé automatiquement au 1er appel API) |
| `data/.gitkeep` | Tracked par git pour garder le dossier data/ |
| `.gitignore` | Exclut node_modules/, data/data.json, logs |
| `README.md` | Instructions d'installation |

---

## Task 1: Scaffolding

**Files:**
- Create: `suivi-km-fiat/.gitignore`
- Create: `suivi-km-fiat/data/.gitkeep`
- Create: `suivi-km-fiat/README.md`

- [ ] **Step 1: Créer .gitignore**

Contenu de `C:\Users\ntill\Devs\suivi-km-fiat\.gitignore` :
```
node_modules/
data/data.json
*.log
.DS_Store
Thumbs.db
```

- [ ] **Step 2: Créer data/.gitkeep**

Créer un fichier vide `C:\Users\ntill\Devs\suivi-km-fiat\data\.gitkeep` (garde le dossier data/ dans git sans tracker data.json).

- [ ] **Step 3: Créer README.md**

Contenu de `C:\Users\ntill\Devs\suivi-km-fiat\README.md` :
```markdown
# 🚗 Suivi Kilométrique Fiat 500e — Contrat LOA

Application web de suivi kilométrique. Stack : PHP + vanilla JS. Stockage : data/data.json.

## Déploiement sur Synology NAS (Web Station)

1. Copier tous les fichiers dans le dossier web du NAS (ex: `web/suivi-km-fiat/`)
2. Accéder via `http://[IP-NAS]/suivi-km-fiat/`

## Développement local (nécessite PHP CLI)

```bash
php -S localhost:8080
```
Puis ouvrir http://localhost:8080

## Données

Stockées dans `data/data.json` (créé automatiquement, exclu de git).
```

- [ ] **Step 4: Initialiser git et commiter**

```bash
cd /c/Users/ntill/Devs/suivi-km-fiat
git init
git add .gitignore data/.gitkeep README.md docs/
git commit -m "chore: project scaffolding"
```

---

## Task 2: api.php — config endpoints

**Files:**
- Create: `suivi-km-fiat/api.php`

- [ ] **Step 1: Vérifier que le test échoue (avant code)**

Copier le dossier `suivi-km-fiat/` dans `Y:\web\suivi-km-fiat\` (ou équivalent sur le NAS), puis ouvrir dans le navigateur :
```
http://[IP-NAS]/suivi-km-fiat/api.php?action=config
```
Résultat attendu : erreur 404 (api.php n'existe pas encore).

- [ ] **Step 2: Créer api.php avec les helpers + endpoints config**

Contenu complet de `C:\Users\ntill\Devs\suivi-km-fiat\api.php` :
```php
<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

define('DATA_FILE', __DIR__ . '/data/data.json');

$defaultData = [
    'config' => [
        'vehicle'        => 'Fiat 500e (AM 2022)',
        'startDate'      => '2025-08-06',
        'startKm'        => 7462,
        'durationMonths' => 50,
        'totalKm'        => 41167
    ],
    'entries' => []
];

function loadData() {
    global $defaultData;
    if (!file_exists(DATA_FILE)) {
        saveData($defaultData);
        return $defaultData;
    }
    $data = json_decode(file_get_contents(DATA_FILE), true);
    return $data ?? $defaultData;
}

function saveData($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = $_GET['action'] ?? '';
$data   = loadData();

switch ($action) {
    case 'config':
        echo json_encode($data['config']);
        break;

    case 'update_config':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        foreach (['vehicle', 'startDate'] as $key) {
            if (isset($input[$key])) $data['config'][$key] = (string)$input[$key];
        }
        foreach (['startKm', 'durationMonths', 'totalKm'] as $key) {
            if (isset($input[$key])) $data['config'][$key] = (float)$input[$key];
        }
        saveData($data);
        echo json_encode(['success' => true, 'config' => $data['config']]);
        break;

    case 'entries':
        $entries = $data['entries'];
        usort($entries, fn($a, $b) => strcmp($b['date'], $a['date']));
        echo json_encode($entries);
        break;

    case 'add_entry':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($input['date']) || !isset($input['km'])) {
            http_response_code(400);
            echo json_encode(['error' => 'date and km required']);
            break;
        }
        $entry = [
            'id'    => (int)(microtime(true) * 1000),
            'date'  => (string)$input['date'],
            'km'    => (int)$input['km'],
            'label' => (string)($input['label'] ?? '')
        ];
        $data['entries'][] = $entry;
        saveData($data);
        echo json_encode(['success' => true, 'entry' => $entry]);
        break;

    case 'delete_entry':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'id required']);
            break;
        }
        $id = $input['id'];
        $data['entries'] = array_values(
            array_filter($data['entries'], fn($e) => $e['id'] != $id)
        );
        saveData($data);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => "Unknown action: $action"]);
}
```

- [ ] **Step 3: Copier sur le NAS et tester GET config**

Copier `api.php` dans `Y:\web\suivi-km-fiat\` (adapter le chemin selon la config Web Station), puis ouvrir :
```
http://[IP-NAS]/suivi-km-fiat/api.php?action=config
```
Résultat attendu (JSON) :
```json
{"vehicle":"Fiat 500e (AM 2022)","startDate":"2025-08-06","startKm":7462,"durationMonths":50,"totalKm":41167}
```
Vérifier aussi que `data/data.json` a été créé automatiquement sur le NAS.

- [ ] **Step 4: Tester update_config via curl (ou Postman)**

```bash
curl -X POST "http://[IP-NAS]/suivi-km-fiat/api.php?action=update_config" \
  -H "Content-Type: application/json" \
  -d '{"totalKm": 41167}'
```
Résultat attendu : `{"success":true,"config":{...}}`

- [ ] **Step 5: Commiter api.php**

```bash
cd /c/Users/ntill/Devs/suivi-km-fiat
git add api.php
git commit -m "feat: add api.php with config and entries endpoints"
```

---

## Task 3: Tester les endpoints entries

- [ ] **Step 1: Tester add_entry**

```bash
curl -X POST "http://[IP-NAS]/suivi-km-fiat/api.php?action=add_entry" \
  -H "Content-Type: application/json" \
  -d '{"date":"2025-10-09","km":9940,"label":"Chrono 2"}'
```
Résultat attendu : `{"success":true,"entry":{"id":...,"date":"2025-10-09","km":9940,"label":"Chrono 2"}}`

- [ ] **Step 2: Tester GET entries**

Ouvrir dans le navigateur :
```
http://[IP-NAS]/suivi-km-fiat/api.php?action=entries
```
Résultat attendu : `[{"id":...,"date":"2025-10-09","km":9940,"label":"Chrono 2"}]`

- [ ] **Step 3: Tester delete_entry**

Récupérer l'`id` de l'entrée créée à l'étape 1, puis :
```bash
curl -X POST "http://[IP-NAS]/suivi-km-fiat/api.php?action=delete_entry" \
  -H "Content-Type: application/json" \
  -d '{"id": [ID_ICI]}'
```
Résultat attendu : `{"success":true}`. Vérifier via GET entries que la liste est vide.

---

## Task 4: index.html — structure complète

**Files:**
- Create: `suivi-km-fiat/index.html`

- [ ] **Step 1: Créer index.html complet**

Contenu complet de `C:\Users\ntill\Devs\suivi-km-fiat\index.html` :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Suivi km — Fiat 500e</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --red:    #c62828;
      --green:  #2e7d32;
      --blue:   #1565c0;
      --bg:     #f4f4f6;
      --card:   #ffffff;
      --text:   #212121;
      --muted:  #757575;
      --border: #e0e0e0;
      --fiat:   #c62828;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      padding: 16px;
      max-width: 820px;
      margin: 0 auto;
    }

    h1 { font-size: 1.4rem; color: var(--fiat); margin-bottom: 16px; }
    h2 { font-size: 0.8rem; color: var(--muted); text-transform: uppercase;
         letter-spacing: 0.06em; margin-bottom: 14px; }

    .card {
      background: var(--card);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 16px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }

    /* ── Tabs ── */
    .tabs { display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap; }
    .tab {
      padding: 8px 18px; border-radius: 8px; font-size: 0.9rem;
      cursor: pointer; background: var(--card);
      border: 1px solid var(--border); color: var(--muted);
    }
    .tab.active { background: var(--fiat); color: white; border-color: var(--fiat); }
    .section { display: none; }
    .section.active { display: block; }

    /* ── Dashboard ── */
    .dash-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 16px; margin-bottom: 16px;
    }
    .dash-item { text-align: center; padding: 12px; background: var(--bg); border-radius: 10px; }
    .dash-item .value { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
    .dash-item .label { font-size: 0.72rem; color: var(--muted); margin-top: 4px; }
    .over    { color: var(--red); }
    .under   { color: var(--green); }
    .neutral { color: var(--blue); }

    .progress-bar-wrap {
      background: var(--border); border-radius: 6px;
      height: 10px; overflow: hidden; margin: 12px 0 4px;
    }
    .progress-bar { height: 100%; border-radius: 6px; background: var(--fiat); transition: width 0.4s; }
    .progress-label { font-size: 0.78rem; color: var(--muted); text-align: right; }
    .summary-text {
      font-size: 0.88rem; line-height: 1.7;
      margin-top: 14px; padding: 12px 14px;
      background: var(--bg); border-radius: 8px;
    }

    /* ── Forms ── */
    .form-row {
      display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;
    }
    .form-group { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 120px; }
    label { font-size: 0.78rem; color: var(--muted); }
    input[type="date"], input[type="number"], input[type="text"] {
      padding: 9px 11px; border: 1px solid var(--border);
      border-radius: 8px; font-size: 0.95rem; width: 100%; background: #fafafa;
    }
    input:focus { outline: none; border-color: var(--fiat); }

    button { padding: 9px 20px; border: none; border-radius: 8px; font-size: 0.9rem; cursor: pointer; }
    .btn-primary  { background: var(--fiat); color: white; }
    .btn-primary:hover  { background: #a31515; }
    .btn-secondary { background: var(--border); color: var(--text); }
    .btn-secondary:hover { background: #d0d0d0; }
    .btn-danger { background: transparent; color: var(--muted); font-size: 1rem; padding: 4px 8px; }
    .btn-danger:hover { color: var(--red); }

    /* ── Table ── */
    table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
    th { text-align: left; padding: 8px 6px; color: var(--muted); font-weight: 500;
         border-bottom: 2px solid var(--border); white-space: nowrap; }
    td { padding: 9px 6px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    .tag { font-size: 0.73rem; background: var(--bg); padding: 2px 7px;
           border-radius: 4px; color: var(--muted); }

    /* ── Config ── */
    .config-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
      gap: 14px; margin-bottom: 16px;
    }

    @media (max-width: 480px) {
      .dash-item .value { font-size: 1.2rem; }
      .tabs { gap: 4px; }
      .tab { padding: 7px 12px; font-size: 0.82rem; }
    }
  </style>
</head>
<body>

<h1>🚗 Suivi km — Fiat 500e</h1>

<div class="tabs">
  <button class="tab active" data-tab="dashboard">Dashboard</button>
  <button class="tab" data-tab="saisie">Saisir</button>
  <button class="tab" data-tab="historique">Historique</button>
  <button class="tab" data-tab="parametres">Paramètres</button>
</div>

<!-- Dashboard -->
<div id="tab-dashboard" class="section active card">
  <h2>Situation actuelle</h2>
  <div class="dash-grid">
    <div class="dash-item">
      <div class="value neutral" id="d-current">—</div>
      <div class="label">Km actuels</div>
    </div>
    <div class="dash-item">
      <div class="value neutral" id="d-allowed">—</div>
      <div class="label">Autorisés aujourd'hui</div>
    </div>
    <div class="dash-item">
      <div class="value" id="d-gap">—</div>
      <div class="label">Écart</div>
    </div>
    <div class="dash-item">
      <div class="value neutral" id="d-proj">—</div>
      <div class="label">Projection fin contrat</div>
    </div>
  </div>
  <div class="progress-bar-wrap">
    <div class="progress-bar" id="progress-bar" style="width:0%"></div>
  </div>
  <div class="progress-label" id="progress-label"></div>
  <div class="summary-text" id="summary-text">Chargement...</div>
</div>

<!-- Saisie -->
<div id="tab-saisie" class="section card">
  <h2>Nouvelle saisie</h2>
  <div class="form-row">
    <div class="form-group">
      <label for="f-date">Date</label>
      <input type="date" id="f-date">
    </div>
    <div class="form-group">
      <label for="f-km">Km compteur</label>
      <input type="number" id="f-km" placeholder="ex: 12 500" min="0">
    </div>
    <div class="form-group">
      <label for="f-label">Libellé (optionnel)</label>
      <input type="text" id="f-label" placeholder="ex: Chrono 3">
    </div>
    <button class="btn-primary" id="btn-save-entry">Enregistrer</button>
  </div>
  <div id="saisie-msg" style="margin-top:10px;font-size:0.85rem;min-height:20px;"></div>
</div>

<!-- Historique -->
<div id="tab-historique" class="section card">
  <h2>Historique des relevés</h2>
  <div style="overflow-x:auto">
    <table>
      <thead><tr>
        <th>Date</th><th>Km</th><th>Δ km</th><th>Écart LOA</th><th>Libellé</th><th></th>
      </tr></thead>
      <tbody id="historique-body">
        <tr><td colspan="6" style="color:var(--muted)">Aucune saisie</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Paramètres -->
<div id="tab-parametres" class="section card">
  <h2>Paramètres du contrat LOA</h2>
  <div class="config-grid">
    <div class="form-group">
      <label for="c-vehicle">Véhicule</label>
      <input type="text" id="c-vehicle">
    </div>
    <div class="form-group">
      <label for="c-startDate">Date début contrat</label>
      <input type="date" id="c-startDate">
    </div>
    <div class="form-group">
      <label for="c-startKm">Km au départ</label>
      <input type="number" id="c-startKm" min="0">
    </div>
    <div class="form-group">
      <label for="c-duration">Durée (mois)</label>
      <input type="number" id="c-duration" min="1">
    </div>
    <div class="form-group">
      <label for="c-totalKm">Km total autorisé</label>
      <input type="number" id="c-totalKm" min="0">
    </div>
  </div>
  <div style="display:flex;gap:10px;align-items:center">
    <button class="btn-primary" id="btn-save-config">Enregistrer</button>
    <span id="config-msg" style="font-size:0.85rem;"></span>
  </div>
</div>

<script>
  const API = './api.php';
  let state = { config: null, entries: [] };

  // ─── Init ─────────────────────────────────────────────────────────
  async function init() {
    document.getElementById('f-date').value = todayISO();
    setupTabs();
    document.getElementById('btn-save-entry').addEventListener('click', addEntry);
    document.getElementById('btn-save-config').addEventListener('click', saveConfig);
    await loadAll();
  }

  async function loadAll() {
    try {
      const [config, entries] = await Promise.all([
        fetch(`${API}?action=config`).then(r => r.json()),
        fetch(`${API}?action=entries`).then(r => r.json())
      ]);
      state.config  = config;
      state.entries = entries;
      renderDashboard();
      renderHistorique();
      fillConfigForm();
    } catch (e) {
      document.getElementById('summary-text').textContent =
        'Erreur de connexion à api.php. Vérifiez que le serveur PHP est actif.';
    }
  }

  // ─── Tabs ─────────────────────────────────────────────────────────
  function setupTabs() {
    document.querySelectorAll('.tab').forEach(btn => {
      btn.addEventListener('click', () => {
        const name = btn.dataset.tab;
        document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(`tab-${name}`).classList.add('active');
      });
    });
  }

  // ─── Dashboard ────────────────────────────────────────────────────
  function renderDashboard() {
    const { config, entries } = state;
    if (!config) return;

    const now        = new Date();
    const startDate  = new Date(config.startDate + 'T00:00:00');
    const monthly    = config.totalKm / config.durationMonths;
    const msPerMonth = 1000 * 60 * 60 * 24 * 30.4375;
    const elapsed    = Math.max(0, (now - startDate) / msPerMonth);
    const remaining  = Math.max(0, config.durationMonths - elapsed);

    const allowedToday = config.startKm + elapsed * monthly;

    const sorted  = [...entries].sort((a, b) => b.date.localeCompare(a.date));
    const latest  = sorted[0];
    const current = latest ? latest.km : config.startKm;

    const driven  = current - config.startKm;
    const gap     = current - allowedToday;
    const pace    = elapsed > 0 ? driven / elapsed : 0;
    const endKm   = config.startKm + config.totalKm;
    const proj    = current + remaining * pace;
    const projGap = proj - endKm;
    const pct     = Math.min(100, (driven / config.totalKm) * 100);

    // Km actuels
    setEl('d-current', fmtKm(current), 'neutral');
    // Autorisés
    setEl('d-allowed', fmtKm(Math.round(allowedToday)), 'neutral');
    // Écart
    setEl('d-gap', (gap >= 0 ? '+' : '') + fmtKm(Math.round(gap)), gap >= 0 ? 'over' : 'under');
    // Projection
    setEl('d-proj', fmtKm(Math.round(proj)) + (projGap >= 0 ? ' ⚠' : ' ✓'), projGap >= 0 ? 'over' : 'under');

    document.getElementById('progress-bar').style.width = pct.toFixed(1) + '%';
    document.getElementById('progress-label').textContent =
      `${fmtKm(driven)} parcourus sur ${fmtKm(config.totalKm)} autorisés (${pct.toFixed(1)}%)`;

    const gStr  = (gap >= 0 ? '+' : '') + fmtKm(Math.round(gap));
    const pgStr = (projGap >= 0 ? '+' : '') + fmtKm(Math.round(projGap));
    document.getElementById('summary-text').innerHTML =
      `Km actuels : <strong>${fmtKm(current)}</strong> — ` +
      `Autorisés ce jour : <strong>${fmtKm(Math.round(allowedToday))}</strong> — ` +
      `Écart : <strong class="${gap >= 0 ? 'over' : 'under'}">${gStr}</strong><br>` +
      `Projection fin contrat : <strong>${fmtKm(Math.round(proj))}</strong> ` +
      `vs <strong>${fmtKm(endKm)}</strong> autorisés → ` +
      `<strong class="${projGap >= 0 ? 'over' : 'under'}">${pgStr}</strong>`;
  }

  function setEl(id, text, cls) {
    const el = document.getElementById(id);
    el.textContent = text;
    el.className   = 'value ' + cls;
  }

  // ─── Historique ───────────────────────────────────────────────────
  function renderHistorique() {
    const { config, entries } = state;
    const tbody = document.getElementById('historique-body');
    if (!entries.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="color:var(--muted)">Aucune saisie</td></tr>';
      return;
    }

    const sorted     = [...entries].sort((a, b) => b.date.localeCompare(a.date));
    const monthly    = config.totalKm / config.durationMonths;
    const msPerMonth = 1000 * 60 * 60 * 24 * 30.4375;

    tbody.innerHTML = sorted.map((entry, i) => {
      const prev  = sorted[i + 1];
      const delta = entry.km - (prev ? prev.km : config.startKm);

      const entryDate      = new Date(entry.date + 'T00:00:00');
      const startDate      = new Date(config.startDate + 'T00:00:00');
      const elapsedAtEntry = (entryDate - startDate) / msPerMonth;
      const allowedAt      = config.startKm + elapsedAtEntry * monthly;
      const gapAt          = entry.km - allowedAt;
      const gapSign        = gapAt >= 0 ? '+' : '';
      const gapClass       = gapAt >= 0 ? 'over' : 'under';

      return `<tr>
        <td>${fmtDate(entry.date)}</td>
        <td><strong>${fmtKm(entry.km)}</strong></td>
        <td style="color:var(--muted)">+${fmtKm(delta)}</td>
        <td class="${gapClass}">${gapSign}${fmtKm(Math.round(gapAt))}</td>
        <td>${entry.label ? `<span class="tag">${escHtml(entry.label)}</span>` : ''}</td>
        <td><button class="btn-danger" data-id="${entry.id}" title="Supprimer">🗑</button></td>
      </tr>`;
    }).join('');

    tbody.querySelectorAll('.btn-danger').forEach(btn => {
      btn.addEventListener('click', () => deleteEntry(Number(btn.dataset.id)));
    });
  }

  // ─── Saisie ───────────────────────────────────────────────────────
  async function addEntry() {
    const date  = document.getElementById('f-date').value;
    const km    = parseInt(document.getElementById('f-km').value, 10);
    const label = document.getElementById('f-label').value.trim();
    const msg   = document.getElementById('saisie-msg');

    if (!date || isNaN(km) || km < 0) {
      showMsg('saisie-msg', '⚠ Date et kilométrage requis.', 'var(--red)');
      return;
    }

    const res = await fetch(`${API}?action=add_entry`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ date, km, label })
    }).then(r => r.json());

    if (res.success) {
      document.getElementById('f-km').value    = '';
      document.getElementById('f-label').value = '';
      showMsg('saisie-msg', `✓ ${fmtKm(km)} enregistrés.`, 'var(--green)', 3000);
      await loadAll();
    }
  }

  async function deleteEntry(id) {
    if (!confirm('Supprimer cette saisie ?')) return;
    await fetch(`${API}?action=delete_entry`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    await loadAll();
  }

  // ─── Config ───────────────────────────────────────────────────────
  function fillConfigForm() {
    const c = state.config;
    if (!c) return;
    document.getElementById('c-vehicle').value   = c.vehicle        ?? '';
    document.getElementById('c-startDate').value = c.startDate      ?? '';
    document.getElementById('c-startKm').value   = c.startKm        ?? '';
    document.getElementById('c-duration').value  = c.durationMonths ?? '';
    document.getElementById('c-totalKm').value   = c.totalKm        ?? '';
  }

  async function saveConfig() {
    const payload = {
      vehicle:        document.getElementById('c-vehicle').value,
      startDate:      document.getElementById('c-startDate').value,
      startKm:        parseFloat(document.getElementById('c-startKm').value),
      durationMonths: parseFloat(document.getElementById('c-duration').value),
      totalKm:        parseFloat(document.getElementById('c-totalKm').value)
    };
    const res = await fetch(`${API}?action=update_config`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(r => r.json());

    if (res.success) {
      showMsg('config-msg', '✓ Paramètres enregistrés', 'var(--green)', 3000);
      await loadAll();
    }
  }

  // ─── Helpers ──────────────────────────────────────────────────────
  function fmtKm(n) {
    return new Intl.NumberFormat('fr-FR').format(n) + ' km';
  }

  function fmtDate(iso) {
    return new Date(iso + 'T12:00:00').toLocaleDateString('fr-FR');
  }

  function todayISO() {
    return new Date().toISOString().slice(0, 10);
  }

  function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function showMsg(id, text, color, clearAfter = 0) {
    const el = document.getElementById(id);
    el.textContent  = text;
    el.style.color  = color;
    if (clearAfter) setTimeout(() => el.textContent = '', clearAfter);
  }

  init();
</script>
</body>
</html>
```

- [ ] **Step 2: Copier index.html sur le NAS et tester dans le navigateur**

Copier `index.html` dans `Y:\web\suivi-km-fiat\` puis ouvrir :
```
http://[IP-NAS]/suivi-km-fiat/
```
Vérifications :
- [ ] Dashboard s'affiche avec les 4 tuiles (km actuels, autorisés, écart, projection)
- [ ] Tabs Dashboard / Saisir / Historique / Paramètres fonctionnent
- [ ] Paramètres LOA pré-remplis (Fiat 500e, 2025-08-06, 7462 km, 50 mois, 41167 km)

- [ ] **Step 3: Saisir l'entrée exemple (Chrono 2)**

Dans l'onglet "Saisir" :
- Date : 09/10/2025
- Km : 9940
- Libellé : Chrono 2
- Cliquer Enregistrer

Résultat attendu dans le Dashboard :
- Km actuels : 9 940 km
- Écart : +749 km environ (rouge)
- Projection : légèrement au-dessus de 48 629 km

- [ ] **Step 4: Vérifier l'Historique**

Onglet Historique : une ligne avec 09/10/2025 / 9 940 km / Écart rouge / "Chrono 2".

- [ ] **Step 5: Commiter index.html**

```bash
cd /c/Users/ntill/Devs/suivi-km-fiat
git add index.html
git commit -m "feat: add complete SPA interface"
```

---

## Task 5: Créer le repo GitHub et pousser

- [ ] **Step 1: Vérifier que gh CLI est authentifié**

```bash
gh auth status
```
Si non authentifié : `gh auth login` et suivre les instructions.

- [ ] **Step 2: Créer le repo et pousser**

```bash
cd /c/Users/ntill/Devs/suivi-km-fiat
gh repo create suivi-km-fiat --public --source=. --remote=origin --push
```

Résultat attendu : URL du repo GitHub affichée (ex: `https://github.com/[USERNAME]/suivi-km-fiat`).

- [ ] **Step 3: Vérifier le repo sur GitHub**

Ouvrir l'URL affichée et vérifier :
- [ ] `index.html`, `api.php`, `.gitignore`, `README.md` présents
- [ ] `data/data.json` absent (gitignored)
- [ ] `data/.gitkeep` présent

---

## Checklist finale

- [ ] `api.php?action=config` retourne le JSON de config LOA
- [ ] `api.php?action=entries` retourne les entrées
- [ ] add_entry et delete_entry fonctionnent
- [ ] Dashboard affiche km / écart / projection correctement
- [ ] Onglet Saisir enregistre une entrée et rafraîchit le dashboard
- [ ] Onglet Historique affiche delta km et écart LOA par entrée
- [ ] Onglet Paramètres permet de modifier la config LOA
- [ ] Responsive sur mobile (tester depuis smartphone)
- [ ] Repo GitHub créé et code poussé
