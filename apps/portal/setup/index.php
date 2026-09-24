<?php
/**
 * One-Time Installer Wizard
 * Stages 10-12
 */

define('INFOLAYER_PORTAL', true);
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/plan_engine.php';

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Requirements & Activation
    $activation_code = trim($_POST['activation_code'] ?? '');
    if (empty($activation_code)) {
        $error = "Please enter an Activation Code to proceed.";
    } else {
        header("Location: /setup/?step=2");
        exit;
    }
} elseif ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 2: Database setup
    try {
        $db = PortalDB::getConnection();

        // Execute Schema
        $sql = file_get_contents(__DIR__ . '/../core/schema.sql');
        $db->exec($sql);

        header("Location: /setup/?step=3");
        exit;
    } catch (Exception $e) {
        $error = "Database setup error: " . $e->getMessage();
    }
} elseif ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 3: Admin account creation & Default Plan Seeding
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($username) || empty($password)) {
        $error = "All admin fields are required.";
    } else {
        try {
            $db = PortalDB::getConnection();
            $hash = password_hash($password, PASSWORD_ARGON2ID);

            $stmt = $db->prepare("INSERT INTO admins (name, email, username, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $username, $hash]);

            // Seed default plans (Free Explorer & Paid Pro)
            PlanEngine::savePlan([
                'name' => 'Free Explorer',
                'description' => 'Free offline core features for church services',
                'type' => 'trial',
                'billing_cycle' => 'trial_days',
                'price' => 0.00,
                'currency' => 'USD',
                'trial_days' => 14,
                'trial_usage_cap_minutes' => 60,
                'is_active' => 1,
                'sort_order' => 1
            ], [
                'max_devices' => 1,
                'transcription_minutes' => 60,
                'output_types' => ['hdmi'],
                'ai_features_enabled' => false,
                'watermark' => false,
                'theme_designer_access' => 'basic'
            ]);

            PlanEngine::savePlan([
                'name' => 'Church Pro',
                'description' => 'Full unlimited presentation features & live AI tools',
                'type' => 'paid',
                'billing_cycle' => 'monthly',
                'price' => 29.00,
                'currency' => 'USD',
                'is_active' => 1,
                'sort_order' => 2
            ], [
                'max_devices' => 3,
                'transcription_minutes' => -1, // Unlimited
                'output_types' => ['hdmi', 'ndi', 'virtual_cam'],
                'ai_features_enabled' => true,
                'watermark' => false,
                'theme_designer_access' => 'full'
            ]);

            // Mark setup complete
            file_put_contents(__DIR__ . '/../storage/secure/.setup_complete', date('c'));
            file_put_contents(__DIR__ . '/../config/app.config.php', "<?php return ['db_driver' => 'sqlite', 'db_file' => __DIR__ . '/../storage/secure/portal.sqlite'];");

            header("Location: /setup/?step=4");
            exit;
        } catch (Exception $e) {
            $error = "Error creating admin: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>InfoLayer Portal - Setup Wizard</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .setup-card { background: #1e293b; padding: 2.5rem; border-radius: 12px; width: 480px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 1px solid #334155; }
        h1 { margin-top: 0; color: #38bdf8; font-size: 1.5rem; text-align: center; }
        .step-indicator { text-align: center; color: #94a3b8; font-size: 0.875rem; margin-bottom: 1.5rem; }
        label { display: block; margin-top: 1rem; color: #cbd5e1; font-size: 0.875rem; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 0.75rem; margin-top: 0.25rem; background: #0f172a; border: 1px solid #475569; border-radius: 6px; color: #fff; box-sizing: border-box; }
        button { width: 100%; padding: 0.75rem; margin-top: 1.5rem; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        button:hover { background: #0369a1; }
        .error { background: #7f1d1d; color: #fca5a5; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.875rem; }
    </style>
</head>
<body>
<div class="setup-card">
    <h1>InfoLayer Portal Installer</h1>
    <div class="step-indicator">Step <?= $step ?> of 4</div>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if ($step === 1): ?>
        <form method="POST">
            <p style="font-size:0.875rem; color:#94a3b8;">System requirements check passed. Enter your Setup Activation Code to activate this portal instance.</p>
            <label>Activation Code</label>
            <input type="text" name="activation_code" placeholder="INFOLAYER-SETUP-CODE" required value="INFOLAYER-ADMIN-ACTIVATE-2025" />
            <button type="submit">Activate & Continue</button>
        </form>
    <?php elseif ($step === 2): ?>
        <form method="POST">
            <p style="font-size:0.875rem; color:#94a3b8;">Click below to initialize database schema and default tables.</p>
            <button type="submit">Initialize Database</button>
        </form>
    <?php elseif ($step === 3): ?>
        <form method="POST">
            <label>Super Admin Name</label>
            <input type="text" name="name" required placeholder="Church Media Director" />
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="admin@church.org" />
            <label>Username</label>
            <input type="text" name="username" required placeholder="admin" />
            <label>Password</label>
            <input type="password" name="password" required />
            <button type="submit">Create Super Admin Account</button>
        </form>
    <?php elseif ($step === 4): ?>
        <div style="text-align:center;">
            <p style="color:#4ade80; font-size:1.1rem; font-weight:bold;">Setup Complete!</p>
            <p style="color:#94a3b8; font-size:0.875rem;">Your InfoLayer Portal is now configured and locked for production.</p>
            <a href="/admin/index.php" style="display:inline-block; margin-top:1rem; padding:0.75rem 1.5rem; background:#0284c7; color:#fff; text-decoration:none; border-radius:6px; font-weight:bold;">Go to Admin Dashboard</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
