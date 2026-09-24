<?php
/**
 * Payment Gateway Webhook Listener (Paystack & Flutterwave)
 * Stage 14
 */

define('INFOLAYER_PORTAL', true);
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/plan_engine.php';

header('Content-Type: application/json');

$db = PortalDB::getConnection();
$input = file_get_contents('php://input');
$event = json_decode($input, true);

if (!$event) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid webhook JSON payload']);
    exit;
}

$gateway = 'paystack';
$reference = '';
$amount = 0.00;
$status = 'failed';
$customer_email = '';
$plan_id = null;

// Parse Paystack event
if (isset($event['event']) && strpos($event['event'], 'charge.success') !== false) {
    $gateway = 'paystack';
    $data = $event['data'] ?? [];
    $reference = $data['reference'] ?? '';
    $amount = ($data['amount'] ?? 0) / 100; // Paystack is in kobo/cents
    $status = 'successful';
    $customer_email = $data['customer']['email'] ?? '';
    $plan_id = $data['metadata']['plan_id'] ?? null;
}
// Parse Flutterwave event
elseif (isset($event['event']) && $event['event'] === 'charge.completed') {
    $gateway = 'flutterwave';
    $data = $event['data'] ?? [];
    $reference = $data['tx_ref'] ?? '';
    $amount = $data['amount'] ?? 0.00;
    $status = strtolower($data['status'] ?? '') === 'successful' ? 'successful' : 'failed';
    $customer_email = $data['customer']['email'] ?? '';
    $plan_id = $data['meta']['plan_id'] ?? null;
}

if (empty($reference) || empty($customer_email)) {
    echo json_encode(['success' => false, 'error' => 'Missing transaction reference or customer email']);
    exit;
}

// Find church by email
$stmt_c = $db->prepare("SELECT id FROM churches WHERE contact_email = ?");
$stmt_c->execute([$customer_email]);
$church = $stmt_c->fetch();

if (!$church) {
    // Auto-create church record if new registration
    $db->prepare("INSERT INTO churches (name, contact_email) VALUES (?, ?)")->execute([$customer_email, $customer_email]);
    $church_id = $db->lastInsertId();
} else {
    $church_id = $church['id'];
}

// Default to default paid plan if not specified
if (!$plan_id) {
    $plans = PlanEngine::getAllPlans();
    foreach ($plans as $p) {
        if ($p['type'] === 'paid') {
            $plan_id = $p['id'];
            break;
        }
    }
}

// Update or create subscription
$stmt_sub = $db->prepare("SELECT id FROM subscriptions WHERE church_id = ? AND status IN ('trialing', 'active', 'past_due')");
$stmt_sub->execute([$church_id]);
$sub = $stmt_sub->fetch();

$period_end = date('Y-m-d H:i:s', strtotime('+30 days'));

if ($sub) {
    $sub_id = $sub['id'];
    $stmt_u = $db->prepare("UPDATE subscriptions SET status = 'active', plan_id = ?, current_period_end = ? WHERE id = ?");
    $stmt_u->execute([$plan_id, $period_end, $sub_id]);
} else {
    $stmt_i = $db->prepare("INSERT INTO subscriptions (church_id, plan_id, status, starts_at, current_period_end) VALUES (?, ?, 'active', CURRENT_TIMESTAMP, ?)");
    $stmt_i->execute([$church_id, $plan_id, $period_end]);
    $sub_id = $db->lastInsertId();
}

// Record payment log
$stmt_p = $db->prepare("INSERT INTO payments (subscription_id, gateway, amount, currency, status, gateway_reference) VALUES (?, ?, ?, 'USD', ?, ?)");
$stmt_p->execute([$sub_id, $gateway, $amount, $status, $reference]);

echo json_encode([
    'success' => true,
    'message' => 'Webhook processed successfully',
    'gateway' => $gateway,
    'reference' => $reference,
    'subscription_id' => $sub_id,
    'new_status' => 'active'
]);
