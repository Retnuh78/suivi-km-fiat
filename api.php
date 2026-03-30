<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

define('DATA_FILE', __DIR__ . '/data/data.json');

$defaultData = [
    'config' => [
        'vehicule'         => 'Fiat 500e (AM 2022)',
        'dateAnniversaire' => '2025-08-06',
        'kmInitial'        => 7462,
        'dureeMois'        => 50,
        'forfaitTotal'     => 41167
    ],
    'releves' => [
        'chrono-2' => ['date' => '2025-10-09', 'km' => 9940]
    ]
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
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'data':
        echo json_encode(loadData());
        break;

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['config']) || !isset($input['releves'])) {
            http_response_code(400);
            echo json_encode(['error' => 'config and releves required']);
            exit;
        }
        $config = [];
        $config['vehicule']         = (string)($input['config']['vehicule'] ?? 'Fiat 500e');
        $config['dateAnniversaire'] = (string)($input['config']['dateAnniversaire'] ?? '');
        $config['kmInitial']        = (float)($input['config']['kmInitial'] ?? 0);
        $config['dureeMois']        = (int)($input['config']['dureeMois'] ?? 1);
        $config['forfaitTotal']     = (float)($input['config']['forfaitTotal'] ?? 0);

        $releves = [];
        foreach ($input['releves'] as $key => $releve) {
            if (!preg_match('/^chrono-\d+$/', $key)) continue;
            $entry = ['date' => (string)($releve['date'] ?? '')];
            if (isset($releve['km']) && $releve['km'] !== '' && $releve['km'] !== null) {
                $entry['km'] = (float)$releve['km'];
            }
            $releves[$key] = $entry;
        }

        saveData(['config' => $config, 'releves' => $releves]);
        echo json_encode(['success' => true, 'savedAt' => date('H:i')]);
        break;

    case 'backup':
        $data = file_exists(DATA_FILE) ? file_get_contents(DATA_FILE) : json_encode(loadData());
        $timestamp = date('Y-m-d_H-i');
        header('Content-Type: application/json');
        header("Content-Disposition: attachment; filename=\"suivi-km-backup-{$timestamp}.json\"");
        echo $data;
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => "Unknown action: $action"]);
}
