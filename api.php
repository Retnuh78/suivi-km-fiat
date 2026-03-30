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
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = $_GET['action'] ?? '';
$data   = loadData();

if (in_array($action, ['update_config', 'add_entry', 'delete_entry'])
    && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

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
            'km'    => (float)$input['km'],
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
        $before = count($data['entries']);
        $data['entries'] = array_values(
            array_filter($data['entries'], fn($e) => $e['id'] != $id)
        );
        if (count($data['entries']) === $before) {
            http_response_code(404);
            echo json_encode(['error' => 'Entry not found']);
            break;
        }
        saveData($data);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => "Unknown action: $action"]);
}
