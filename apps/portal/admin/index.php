<?php
/**
 * Super Admin Dashboard & Plan Management CRUD Module
 * Stage 12 & Stage 17
 */

define('INFOLAYER_PORTAL', true);
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/plan_engine.php';

$action = $_GET['action'] ?? 'dashboard';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_plan') {
    $plan_id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $data = [
        'id' => $plan_id,
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'type' => $_POST['type'] ?? 'paid',
        'billing_cycle' => $_POST['billing_cycle'] ?? 'monthly',
        'price' => (float)($_POST['price'] ?? 0.00),
        'currency' => trim($_POST['currency'] ?? 'USD'),
        'trial_days' => !empty($_POST['trial_days']) ? (int)$_POST['trial_days'] : null,
        'trial_usage_cap_minutes' => !empty($_POST['trial_usage_cap_minutes']) ? (int)$_POST['trial_usage_cap_minutes'] : null,
        'fallback_plan_id' => !empty($_POST['fallback_plan_id']) ? (int)$_POST['fallback_plan_id'] : null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'sort_order' => (int)($_POST['sort_order'] ?? 0)
    ];

    $features = [
        'max_devices' => (int)($_POST['feat_max_devices'] ?? 1),
        'transcription_minutes' => (int)($_POST['feat_transcription_minutes'] ?? -1),
        'output_types' => $_POST['feat_output_types'] ?? ['hdmi'],
        'ai_features_enabled' => isset($_POST['feat_ai_features_enabled']),
        'watermark' => isset($_POST['feat_watermark']),
        'theme_designer_access' => $_POST['feat_theme_designer_access'] ?? 'full'
    ];

    try {
        PlanEngine::savePlan($data, $features);
        header("Location: /admin/index.php?action=plans&saved=1");
        exit;
    } catch (Exception $e) {
        $error = "Failed to save plan: " . $e->getMessage();
    }
}

$plans = PlanEngine::getAllPlans();
?>
<!DOCTYPE html>
<html>
<head>
    <title>InfoLayer Admin Dashboard</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 0; }
        .navbar { background: #1e293b; border-bottom: 1px solid #334155; padding: 1rem 2rem; display: flex; justify-between: space-between; align-items: center; }
        .navbar h2 { margin: 0; color: #38bdf8; font-size: 1.25rem; }
        .nav-links a { color: #cbd5e1; text-decoration: none; margin-left: 1.5rem; font-size: 0.875rem; }
        .nav-links a.active { color: #38bdf8; font-weight: bold; }
        .container { padding: 2rem; max-width: 1200px; margin: 0 auto; }
        .card { background: #1e293b; border-radius: 8px; border: 1px solid #334155; padding: 1.5rem; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #334155; }
        th { color: #94a3b8; font-size: 0.875rem; }
        .btn { background: #0284c7; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 0.875rem; display: inline-block; }
        .btn:hover { background: #0369a1; }
        .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge-trial { background: #f59e0b; color: #000; }
        .badge-paid { background: #10b981; color: #000; }
        label { display: block; margin-top: 1rem; color: #cbd5e1; font-size: 0.875rem; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 0.5rem; margin-top: 0.25rem; background: #0f172a; border: 1px solid #475569; border-radius: 6px; color: #fff; box-sizing: border-box; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    </style>
</head>
<body>
<div class="navbar">
    <h2>InfoLayer Admin Portal</h2>
    <div class="nav-links">
        <a href="/admin/index.php?action=dashboard" class="<?= $action === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="/admin/index.php?action=plans" class="<?= $action === 'plans' ? 'active' : '' ?>">Plan Management</a>
        <a href="/admin/index.php?action=edit_plan" class="btn">+ Create Custom Plan</a>
    </div>
</div>

<div class="container">
    <?php if ($action === 'dashboard'): ?>
        <div class="card">
            <h3>Overview</h3>
            <p style="color:#94a3b8;">System active and monitoring activation status across connected devices.</p>
            <div style="display:flex; gap:1.5rem; margin-top:1rem;">
                <div style="background:#0f172a; padding:1rem 1.5rem; border-radius:6px; flex:1; border:1px solid #334155;">
                    <div style="color:#94a3b8; font-size:0.875rem;">Total Plans Configured</div>
                    <div style="font-size:1.5rem; font-weight:bold; color:#38bdf8; margin-top:0.25rem;"><?= count($plans) ?></div>
                </div>
                <div style="background:#0f172a; padding:1rem 1.5rem; border-radius:6px; flex:1; border:1px solid #334155;">
                    <div style="color:#94a3b8; font-size:0.875rem;">System Status</div>
                    <div style="font-size:1.5rem; font-weight:bold; color:#4ade80; margin-top:0.25rem;">Operational</div>
                </div>
            </div>
        </div>
    <?php elseif ($action === 'plans'): ?>
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0;">Custom Plans</h3>
                <a href="/admin/index.php?action=edit_plan" class="btn">+ Create New Plan</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Billing Cycle</th>
                        <th>Price</th>
                        <th>Max Devices</th>
                        <th>Transcription Limit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                            <td><span class="badge badge-<?= $p['type'] ?>"><?= $p['type'] ?></span></td>
                            <td><?= htmlspecialchars($p['billing_cycle']) ?></td>
                            <td>$<?= number_format($p['price'], 2) ?> <?= $p['currency'] ?></td>
                            <td><?= $p['features']['max_devices'] ?? 1 ?></td>
                            <td><?= ($p['features']['transcription_minutes'] ?? -1) === -1 ? 'Unlimited' : $p['features']['transcription_minutes'] . ' mins' ?></td>
                            <td>
                                <a href="/admin/index.php?action=edit_plan&id=<?= $p['id'] ?>" style="color:#38bdf8;">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($action === 'edit_plan'):
        $edit_id = $_GET['id'] ?? null;
        $plan_data = $edit_id ? PlanEngine::getPlan((int)$edit_id) : null;
        $current_outputs = $plan_data['features']['output_types'] ?? ['hdmi'];
    ?>
        <div class="card">
            <h3><?= $plan_data ? 'Edit Custom Plan' : 'Create Custom Plan' ?></h3>
            <form method="POST" action="/admin/index.php?action=save_plan">
                <?php if ($plan_data): ?><input type="hidden" name="id" value="<?= $plan_data['id'] ?>"><?php endif; ?>

                <div class="grid-2">
                    <div>
                        <label>Plan Name</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($plan_data['name'] ?? '') ?>" placeholder="e.g., Church Pro">
                    </div>
                    <div>
                        <label>Plan Type</label>
                        <select name="type">
                            <option value="trial" <?= ($plan_data['type'] ?? '') === 'trial' ? 'selected' : '' ?>>Trial Plan</option>
                            <option value="paid" <?= ($plan_data['type'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid Subscription</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label>Billing Cycle</label>
                        <select name="billing_cycle">
                            <option value="monthly" <?= ($plan_data['billing_cycle'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="quarterly" <?= ($plan_data['billing_cycle'] ?? '') === 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
                            <option value="yearly" <?= ($plan_data['billing_cycle'] ?? '') === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                            <option value="one_time" <?= ($plan_data['billing_cycle'] ?? '') === 'one_time' ? 'selected' : '' ?>>One-Time / Lifetime</option>
                            <option value="trial_days" <?= ($plan_data['billing_cycle'] ?? '') === 'trial_days' ? 'selected' : '' ?>>Trial Days</option>
                        </select>
                    </div>
                    <div>
                        <label>Price</label>
                        <input type="number" step="0.01" name="price" value="<?= $plan_data['price'] ?? 0.00 ?>">
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label>Trial Period (Days)</label>
                        <input type="number" name="trial_days" value="<?= $plan_data['trial_days'] ?? 14 ?>" placeholder="e.g. 14">
                    </div>
                    <div>
                        <label>Trial Usage Cap (Transcription Minutes)</label>
                        <input type="number" name="trial_usage_cap_minutes" value="<?= $plan_data['trial_usage_cap_minutes'] ?? 60 ?>" placeholder="e.g. 60">
                    </div>
                </div>

                <h4 style="color:#38bdf8; margin-top:1.5rem; margin-bottom:0.5rem;">Feature Flags & Capabilities</h4>
                <div class="grid-2">
                    <div>
                        <label>Max Allowed Devices per Activation</label>
                        <input type="number" name="feat_max_devices" value="<?= $plan_data['features']['max_devices'] ?? 1 ?>">
                    </div>
                    <div>
                        <label>Transcription Minutes (-1 for Unlimited)</label>
                        <input type="number" name="feat_transcription_minutes" value="<?= $plan_data['features']['transcription_minutes'] ?? -1 ?>">
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label>Theme Designer Access Tier</label>
                        <select name="feat_theme_designer_access">
                            <option value="none" <?= ($plan_data['features']['theme_designer_access'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                            <option value="basic" <?= ($plan_data['features']['theme_designer_access'] ?? '') === 'basic' ? 'selected' : '' ?>>Basic Tier</option>
                            <option value="full" <?= ($plan_data['features']['theme_designer_access'] ?? 'full') === 'full' ? 'selected' : '' ?>>Full Designer Tier</option>
                        </select>
                    </div>
                    <div>
                        <label>Allowed Output Types</label>
                        <div style="display:flex; gap:1rem; margin-top:0.5rem;">
                            <label style="display:inline-flex; align-items:center; gap:0.25rem; margin-top:0;">
                                <input type="checkbox" name="feat_output_types[]" value="hdmi" <?= in_array('hdmi', $current_outputs) ? 'checked' : '' ?>> HDMI Output
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:0.25rem; margin-top:0;">
                                <input type="checkbox" name="feat_output_types[]" value="ndi" <?= in_array('ndi', $current_outputs) ? 'checked' : '' ?>> NDI Output
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:0.25rem; margin-top:0;">
                                <input type="checkbox" name="feat_output_types[]" value="virtual_cam" <?= in_array('virtual_cam', $current_outputs) ? 'checked' : '' ?>> Virtual Camera
                            </label>
                        </div>
                    </div>
                </div>

                <div style="margin-top:1rem; display:flex; gap:2rem;">
                    <label style="display:inline-flex; align-items:center; gap:0.5rem; cursor:pointer;">
                        <input type="checkbox" name="feat_ai_features_enabled" <?= !empty($plan_data['features']['ai_features_enabled']) ? 'checked' : '' ?>>
                        Enable AI Features (Sermon Notes, AI Slide Gen)
                    </label>
                    <label style="display:inline-flex; align-items:center; gap:0.5rem; cursor:pointer;">
                        <input type="checkbox" name="feat_watermark" <?= !empty($plan_data['features']['watermark']) ? 'checked' : '' ?>>
                        Apply Watermark to Presentation Output
                    </label>
                </div>

                <button type="submit" class="btn" style="margin-top:1.5rem;">Save Plan Configuration</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
