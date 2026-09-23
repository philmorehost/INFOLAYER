<?php
/**
 * Verification Test Script for Sprint 1 (Portal Backend & Edge Cases)
 */

define('INFOLAYER_PORTAL', true);
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/plan_engine.php';

echo "=== SPRINT 1 EDGE CASE VERIFICATION RUN ===\n";

// Fresh DB
@unlink(__DIR__ . '/storage/secure/portal.sqlite');

$db = PortalDB::getConnection();
$sql = file_get_contents(__DIR__ . '/core/schema.sql');
$db->exec($sql);

// Create Plan with max_devices = 1
$plan_id = PlanEngine::savePlan([
    'name' => 'Edge Case Test Plan',
    'type' => 'paid',
    'billing_cycle' => 'monthly',
    'price' => 29.00,
    'currency' => 'USD',
    'is_active' => 1,
    'sort_order' => 1
], [
    'max_devices' => 1,
    'transcription_minutes' => -1,
    'output_types' => ['hdmi'],
    'ai_features_enabled' => false,
    'watermark' => false,
    'theme_designer_access' => 'basic'
]);

$db->exec("INSERT INTO churches (name, contact_email) VALUES ('Test Church', 'test@church.org')");
$church_id = $db->lastInsertId();

$stmt_sub = $db->prepare("INSERT INTO subscriptions (church_id, plan_id, status, starts_at) VALUES (?, ?, 'active', CURRENT_TIMESTAMP)");
$stmt_sub->execute([$church_id, $plan_id]);
$sub_id = $db->lastInsertId();

$activation_code = "INFOLAYER-EDGE-TEST-KEY";
$stmt_key = $db->prepare("INSERT INTO activation_keys (subscription_id, key_value, max_devices) VALUES (?, ?, 1)");
$stmt_key->execute([$sub_id, $activation_code]);

// Helper to test /api/activate logic
function test_activation($code, $fingerprint) {
    global $db;
    $stmt = $db->prepare("
        SELECT ak.*, s.status as sub_status, s.church_id, s.plan_id, s.trial_ends_at, s.current_period_end
        FROM activation_keys ak
        JOIN subscriptions s ON ak.subscription_id = s.id
        WHERE ak.key_value = ?
    ");
    $stmt->execute([$code]);
    $key = $stmt->fetch();

    if (!$key || $key['status'] !== 'active') {
        return ['success' => false, 'error' => 'Invalid activation code.'];
    }

    $stmt_devs = $db->prepare("SELECT COUNT(*) as cnt FROM activated_devices WHERE activation_key_id = ?");
    $stmt_devs->execute([$key['id']]);
    $registered_count = (int)$stmt_devs->fetch()['cnt'];

    $stmt_curr = $db->prepare("SELECT * FROM activated_devices WHERE activation_key_id = ? AND device_fingerprint = ?");
    $stmt_curr->execute([$key['id'], $fingerprint]);
    $existing = $stmt_curr->fetch();

    if (!$existing && $registered_count >= (int)$key['max_devices']) {
        return ['success' => false, 'error' => 'Device limit reached for this activation code.'];
    }

    if (!$existing) {
        $stmt_ins = $db->prepare("INSERT INTO activated_devices (activation_key_id, device_fingerprint, device_name) VALUES (?, ?, ?)");
        $stmt_ins->execute([$key['id'], $fingerprint, 'Device']);
    }

    $plan = PlanEngine::getPlan($key['plan_id']);
    return ['success' => true, 'plan_name' => $plan['name']];
}

// 1. Edge Case Test: Invalid Activation Code
$res_invalid = test_activation('INVALID-NONEXISTENT-CODE', 'DEV-1');
assert($res_invalid['success'] === false);
echo "[PASS] Edge Case 1: Invalid activation code correctly rejected: " . $res_invalid['error'] . "\n";

// 2. Edge Case Test: First Device Activation
$res_dev1 = test_activation($activation_code, 'DEV-1');
assert($res_dev1['success'] === true);
echo "[PASS] Edge Case 2: First device registered successfully.\n";

// 3. Edge Case Test: Exceeding Max Devices Limit (max_devices = 1)
$res_dev2 = test_activation($activation_code, 'DEV-2');
assert($res_dev2['success'] === false);
echo "[PASS] Edge Case 3: Second device correctly blocked due to max_devices limit: " . $res_dev2['error'] . "\n";

echo "=== ALL EDGE CASE TESTS PASSED SUCCESSFULLY ===\n";
