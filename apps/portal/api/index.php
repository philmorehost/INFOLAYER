<?php
/**
 * Activation API Endpoint
 * Handles: /api/activate, /api/validate, /api/deactivate
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/plan_engine.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$db = PortalDB::getConnection();

if (strpos($uri, '/api/activate') !== false && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $key_value = trim($input['activation_code'] ?? $input['activation_key'] ?? '');
    $device_fingerprint = trim($input['device_fingerprint'] ?? '');
    $device_name = trim($input['device_name'] ?? 'Church PC');

    if (empty($key_value) || empty($device_fingerprint)) {
        echo json_encode(['success' => false, 'error' => 'Activation code and device fingerprint are required.']);
        exit;
    }

    // Lookup activation key
    $stmt = $db->prepare("
        SELECT ak.*, s.status as sub_status, s.church_id, s.plan_id, s.trial_ends_at, s.current_period_end
        FROM activation_keys ak
        JOIN subscriptions s ON ak.subscription_id = s.id
        WHERE ak.key_value = ?
    ");
    $stmt->execute([$key_value]);
    $key = $stmt->fetch();

    if (!$key) {
        echo json_encode(['success' => false, 'error' => 'Invalid activation code.']);
        exit;
    }

    if ($key['status'] !== 'active') {
        echo json_encode(['success' => false, 'error' => 'This activation code is revoked or expired.']);
        exit;
    }

    // Check device registrations
    $stmt_devs = $db->prepare("SELECT COUNT(*) as cnt FROM activated_devices WHERE activation_key_id = ?");
    $stmt_devs->execute([$key['id']]);
    $registered_count = (int)$stmt_devs->fetch()['cnt'];

    // Check if this device is already registered
    $stmt_curr = $db->prepare("SELECT * FROM activated_devices WHERE activation_key_id = ? AND device_fingerprint = ?");
    $stmt_curr->execute([$key['id'], $device_fingerprint]);
    $existing = $stmt_curr->fetch();

    if (!$existing && $registered_count >= (int)$key['max_devices']) {
        echo json_encode(['success' => false, 'error' => 'Device limit reached for this activation code.']);
        exit;
    }

    if ($existing) {
        $stmt_upd = $db->prepare("UPDATE activated_devices SET last_validated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt_upd->execute([$existing['id']]);
    } else {
        $stmt_ins = $db->prepare("INSERT INTO activated_devices (activation_key_id, device_fingerprint, device_name) VALUES (?, ?, ?)");
        $stmt_ins->execute([$key['id'], $device_fingerprint, $device_name]);
    }

    // Fetch plan & features
    $plan = PlanEngine::getPlan($key['plan_id']);

    echo json_encode([
        'success' => true,
        'message' => 'Activation successful.',
        'activation_code' => $key_value,
        'subscription_status' => $key['sub_status'],
        'plan_name' => $plan['name'] ?? 'Custom Plan',
        'plan_type' => $plan['type'] ?? 'paid',
        'trial_ends_at' => $key['trial_ends_at'],
        'current_period_end' => $key['current_period_end'],
        'max_devices' => $key['max_devices'],
        'features' => $plan['features'] ?? []
    ]);
    exit;

} elseif (strpos($uri, '/api/validate') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;
    $key_value = trim($input['activation_code'] ?? $input['activation_key'] ?? '');
    $device_fingerprint = trim($input['device_fingerprint'] ?? '');

    if (empty($key_value) || empty($device_fingerprint)) {
        echo json_encode(['success' => false, 'error' => 'Missing code or fingerprint.']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT ak.*, s.status as sub_status, s.plan_id, s.trial_ends_at, s.current_period_end
        FROM activation_keys ak
        JOIN subscriptions s ON ak.subscription_id = s.id
        WHERE ak.key_value = ?
    ");
    $stmt->execute([$key_value]);
    $key = $stmt->fetch();

    if (!$key || $key['status'] !== 'active') {
        echo json_encode(['success' => false, 'valid' => false, 'error' => 'Activation code is inactive.']);
        exit;
    }

    // Verify device
    $stmt_dev = $db->prepare("SELECT id FROM activated_devices WHERE activation_key_id = ? AND device_fingerprint = ?");
    $stmt_dev->execute([$key['id'], $device_fingerprint]);
    $dev = $stmt_dev->fetch();

    if (!$dev) {
        echo json_encode(['success' => false, 'valid' => false, 'error' => 'Device not registered.']);
        exit;
    }

    // Update last validated timestamp
    $db->prepare("UPDATE activated_devices SET last_validated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$dev['id']]);

    $plan = PlanEngine::getPlan($key['plan_id']);

    echo json_encode([
        'success' => true,
        'valid' => true,
        'subscription_status' => $key['sub_status'],
        'plan_name' => $plan['name'] ?? 'Custom Plan',
        'features' => $plan['features'] ?? []
    ]);
    exit;

} elseif (strpos($uri, '/api/deactivate') !== false && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $key_value = trim($input['activation_code'] ?? $input['activation_key'] ?? '');
    $device_fingerprint = trim($input['device_fingerprint'] ?? '');

    $stmt = $db->prepare("SELECT id FROM activation_keys WHERE key_value = ?");
    $stmt->execute([$key_value]);
    $key = $stmt->fetch();

    if ($key) {
        $stmt_del = $db->prepare("DELETE FROM activated_devices WHERE activation_key_id = ? AND device_fingerprint = ?");
        $stmt_del->execute([$key['id'], $device_fingerprint]);
    }

    echo json_encode(['success' => true, 'message' => 'Device deactivated.']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid API Endpoint']);
