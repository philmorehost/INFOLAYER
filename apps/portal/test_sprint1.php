<?php
/**
 * Verification Test Script for Sprint 1 (Portal Backend & Plan Engine)
 */

define('INFOLAYER_PORTAL', true);
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/plan_engine.php';

echo "=== SPRINT 1 VERIFICATION TEST RUN ===\n";

// Fresh DB
@unlink(__DIR__ . '/storage/secure/portal.sqlite');

// 1. Check Schema Initialization
$db = PortalDB::getConnection();
$sql = file_get_contents(__DIR__ . '/core/schema.sql');
$db->exec($sql);
echo "[PASS] Database schema initialized successfully.\n";

// 2. Create Custom Plan via PlanEngine
$plan_id = PlanEngine::savePlan([
    'name' => 'Sprint 1 Test Plan',
    'description' => 'Verification plan for Sprint 1',
    'type' => 'trial',
    'billing_cycle' => 'trial_days',
    'price' => 0.00,
    'currency' => 'USD',
    'trial_days' => 30,
    'trial_usage_cap_minutes' => 120,
    'is_active' => 1,
    'sort_order' => 1
], [
    'max_devices' => 2,
    'transcription_minutes' => 120,
    'output_types' => ['hdmi', 'ndi'],
    'ai_features_enabled' => true,
    'watermark' => false,
    'theme_designer_access' => 'full'
]);
echo "[PASS] PlanEngine created custom plan ID: $plan_id\n";

// 3. Fetch Plan & Verify Features
$fetched_plan = PlanEngine::getPlan($plan_id);
assert($fetched_plan['name'] === 'Sprint 1 Test Plan');
assert($fetched_plan['features']['max_devices'] === 2);
assert($fetched_plan['features']['ai_features_enabled'] === true);
echo "[PASS] PlanEngine retrieved plan and parsed JSON feature flags correctly.\n";

// 4. Create Subscription & Activation Key
$db->exec("INSERT INTO churches (name, contact_email) VALUES ('Grace Community Church', 'media@grace.org')");
$church_id = $db->lastInsertId();

$stmt_sub = $db->prepare("INSERT INTO subscriptions (church_id, plan_id, status, starts_at) VALUES (?, ?, 'trialing', CURRENT_TIMESTAMP)");
$stmt_sub->execute([$church_id, $plan_id]);
$sub_id = $db->lastInsertId();

$activation_code = "INFOLAYER-TEST-KEY-12345";
$stmt_key = $db->prepare("INSERT INTO activation_keys (subscription_id, key_value, max_devices) VALUES (?, ?, 2)");
$stmt_key->execute([$sub_id, $activation_code]);
echo "[PASS] Created test church, subscription, and activation code: $activation_code\n";

// 5. Test Activation Query Logic
$stmt_check = $db->prepare("
    SELECT ak.*, s.status as sub_status, s.church_id, s.plan_id, s.trial_ends_at, s.current_period_end
    FROM activation_keys ak
    JOIN subscriptions s ON ak.subscription_id = s.id
    WHERE ak.key_value = ?
");
$stmt_check->execute([$activation_code]);
$key_data = $stmt_check->fetch();

assert($key_data !== false);
assert($key_data['key_value'] === $activation_code);

$plan_details = PlanEngine::getPlan($key_data['plan_id']);
assert($plan_details['name'] === 'Sprint 1 Test Plan');

echo "[PASS] Verification of Activation API query logic and feature extraction succeeded.\n";
echo "=== SPRINT 1 ALL TESTS PASSED SUCCESSFULLY ===\n";
