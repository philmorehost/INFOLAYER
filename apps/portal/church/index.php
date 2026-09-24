<?php
/**
 * Church Self-Service Account Dashboard
 * Stage 17
 */

define('INFOLAYER_PORTAL', true);
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/plan_engine.php';

session_start();

// Demo session fallback for testing
$church_id = $_SESSION['church_id'] ?? 1;

$db = PortalDB::getConnection();

// Fetch church details
$stmt_c = $db->prepare("SELECT * FROM churches WHERE id = ?");
$stmt_c->execute([$church_id]);
$church = $stmt_c->fetch();

// Fetch subscription & activation key
$stmt_sub = $db->prepare("
    SELECT s.*, p.name as plan_name, ak.key_value, ak.max_devices
    FROM subscriptions s
    JOIN plans p ON s.plan_id = p.id
    LEFT JOIN activation_keys ak ON ak.subscription_id = s.id
    WHERE s.church_id = ?
    ORDER BY s.id DESC LIMIT 1
");
$stmt_sub->execute([$church_id]);
$subscription = $stmt_sub->fetch();

// Fetch registered devices
$devices = [];
if (!empty($subscription['key_value'])) {
    $stmt_dev = $db->prepare("
        SELECT ad.*
        FROM activated_devices ad
        JOIN activation_keys ak ON ad.activation_key_id = ak.id
        WHERE ak.key_value = ?
    ");
    $stmt_dev->execute([$subscription['key_value']]);
    $devices = $stmt_dev->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Church Portal - InfoLayer</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 0; }
        .navbar { background: #1e293b; border-bottom: 1px solid #334155; padding: 1rem 2rem; display: flex; justify-between: space-between; align-items: center; }
        .navbar h2 { margin: 0; color: #38bdf8; font-size: 1.25rem; }
        .container { padding: 2rem; max-width: 1000px; margin: 0 auto; }
        .card { background: #1e293b; border-radius: 8px; border: 1px solid #334155; padding: 1.5rem; margin-bottom: 1.5rem; }
        .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge-active { background: #10b981; color: #000; }
        .badge-trialing { background: #f59e0b; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #334155; }
        th { color: #94a3b8; font-size: 0.875rem; }
    </style>
</head>
<body>
<div class="navbar">
    <h2>Church Portal Dashboard</h2>
    <div style="color:#94a3b8; font-size:0.875rem;"><?= htmlspecialchars($church['contact_email'] ?? 'Church Account') ?></div>
</div>

<div class="container">
    <div class="card">
        <h3>Subscription & Activation Details</h3>
        <p style="color:#94a3b8; font-size:0.875rem;">Copy your Activation Code below and enter it into the InfoLayer Desktop App on your church PC.</p>

        <div style="background:#0f172a; padding:1rem; border-radius:6px; border:1px solid #334155; margin-top:1rem; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <div style="color:#94a3b8; font-size:0.75rem;">Activation Code</div>
                <div style="font-family:monospace; font-size:1.1rem; color:#38bdf8; font-weight:bold; margin-top:0.25rem;"><?= htmlspecialchars($subscription['key_value'] ?? 'No active code') ?></div>
            </div>
            <div>
                <span class="badge badge-<?= $subscription['status'] ?? 'trialing' ?>"><?= $subscription['status'] ?? 'Active' ?></span>
            </div>
        </div>

        <div style="display:flex; gap:1rem; margin-top:1rem;">
            <div style="flex:1;">
                <label style="color:#94a3b8; font-size:0.875rem;">Current Plan</label>
                <div style="font-weight:bold; color:#fff;"><?= htmlspecialchars($subscription['plan_name'] ?? 'Free Explorer') ?></div>
            </div>
            <div style="flex:1;">
                <label style="color:#94a3b8; font-size:0.875rem;">Max Allowed Devices</label>
                <div style="font-weight:bold; color:#fff;"><?= $subscription['max_devices'] ?? 1 ?> PC(s)</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Activated Devices (<?= count($devices) ?> / <?= $subscription['max_devices'] ?? 1 ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>Device Name</th>
                    <th>Fingerprint</th>
                    <th>Last Validated</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($devices)): ?>
                    <tr><td colspan="3" style="color:#64748b;">No PCs activated yet. Enter your code in the InfoLayer desktop app.</td></tr>
                <?php else: ?>
                    <?php foreach ($devices as $d): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['device_name']) ?></strong></td>
                            <td style="font-family:monospace; color:#94a3b8;"><?= htmlspecialchars($d['device_fingerprint']) ?></td>
                            <td><?= htmlspecialchars($d['last_validated_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
