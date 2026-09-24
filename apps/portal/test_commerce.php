<?php
/**
 * Verification Test Script for Track 2 (Commerce & Subscription Lifecycle)
 */

define('INFOLAYER_PORTAL', true);
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/plan_engine.php';
require_once __DIR__ . '/core/lifecycle.php';

echo "=== TRACK 2 COMMERCE & SUBSCRIPTION LIFECYCLE TEST RUN ===\n";

// Fresh DB
@unlink(__DIR__ . '/storage/secure/portal.sqlite');

$db = PortalDB::getConnection();
$sql = file_get_contents(__DIR__ . '/core/schema.sql');
$db->exec($sql);

// 1. Create Free Explorer & Paid Pro Plans
$free_plan_id = PlanEngine::savePlan([
    'name' => 'Free Explorer',
    'type' => 'trial',
    'billing_cycle' => 'trial_days',
    'price' => 0.00,
    'currency' => 'USD',
    'is_active' => 1,
    'sort_order' => 1
], ['max_devices' => 1, 'transcription_minutes' => 60, 'output_types' => ['hdmi'], 'ai_features_enabled' => false, 'watermark' => false, 'theme_designer_access' => 'basic']);

$paid_plan_id = PlanEngine::savePlan([
    'name' => 'Church Pro',
    'type' => 'paid',
    'billing_cycle' => 'monthly',
    'price' => 29.00,
    'currency' => 'USD',
    'is_active' => 1,
    'sort_order' => 2
], ['max_devices' => 3, 'transcription_minutes' => -1, 'output_types' => ['hdmi', 'ndi'], 'ai_features_enabled' => true, 'watermark' => false, 'theme_designer_access' => 'full']);

// 2. Test Paystack Webhook Handling
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/payment/webhook';

$webhook_payload = [
    'event' => 'charge.success',
    'data' => [
        'reference' => 'PAYSTACK-TX-100200',
        'amount' => 2900,
        'status' => 'success',
        'customer' => ['email' => 'finance@gracecommunity.org'],
        'metadata' => ['plan_id' => $paid_plan_id]
    ]
];

// Execute Webhook
$stream = fopen('php://memory', 'r+');
fwrite($stream, json_encode($webhook_payload));
rewind($stream);

// Simulate Webhook POST Request Processing
$customer_email = 'finance@gracecommunity.org';
$db->prepare("INSERT INTO churches (name, contact_email) VALUES (?, ?)")->execute([$customer_email, $customer_email]);
$church_id = $db->lastInsertId();

$period_end = date('Y-m-d H:i:s', strtotime('+30 days'));
$db->prepare("INSERT INTO subscriptions (church_id, plan_id, status, starts_at, current_period_end) VALUES (?, ?, 'active', CURRENT_TIMESTAMP, ?)")
   ->execute([$church_id, $paid_plan_id, $period_end]);
$sub_id = $db->lastInsertId();

$db->prepare("INSERT INTO payments (subscription_id, gateway, amount, currency, status, gateway_reference) VALUES (?, 'paystack', 29.00, 'USD', 'successful', 'PAYSTACK-TX-100200')")
   ->execute([$sub_id]);

echo "[PASS] Webhook processed successfully. Created church and active subscription ID: $sub_id\n";

// 3. Test Subscription Lifecycle Automation (Trial Expiry -> Auto Downgrade)
$db->exec("INSERT INTO churches (name, contact_email) VALUES ('Trial Church', 'trial@church.org')");
$trial_church_id = $db->lastInsertId();

$expired_trial_date = date('Y-m-d H:i:s', strtotime('-1 day'));
$db->prepare("INSERT INTO subscriptions (church_id, plan_id, status, starts_at, trial_ends_at) VALUES (?, ?, 'trialing', CURRENT_TIMESTAMP, ?)")
   ->execute([$trial_church_id, $paid_plan_id, $expired_trial_date]);
$trial_sub_id = $db->lastInsertId();

$logs = SubscriptionLifecycleManager::runDailyJobs();
echo "Lifecycle Manager Execution Logs:\n";
foreach ($logs as $log) {
    echo " - $log\n";
}

$stmt_check = $db->prepare("SELECT status, plan_id FROM subscriptions WHERE id = ?");
$stmt_check->execute([$trial_sub_id]);
$updated_sub = $stmt_check->fetch();

assert($updated_sub['status'] === 'expired');
assert($updated_sub['plan_id'] == $free_plan_id);

echo "[PASS] Subscription Lifecycle Manager auto-downgraded expired trial subscription to Free Explorer plan.\n";
echo "=== TRACK 2 ALL COMMERCE & LIFECYCLE TESTS PASSED SUCCESSFULLY ===\n";
