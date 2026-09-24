<?php
/**
 * Subscription Lifecycle Automation Manager
 * Stages 13 & 14
 */

if (!defined('INFOLAYER_PORTAL')) {
    define('INFOLAYER_PORTAL', true);
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/plan_engine.php';

class SubscriptionLifecycleManager {

    /**
     * Run lifecycle checks across all active/trialing/past_due subscriptions
     */
    public static function runDailyJobs(): array {
        $db = PortalDB::getConnection();
        $logs = [];

        $now = date('Y-m-d H:i:s');

        // 1. Check expired trials -> Auto-downgrade to Explorer Free Plan
        $stmt_trials = $db->prepare("
            SELECT s.*, p.fallback_plan_id
            FROM subscriptions s
            JOIN plans p ON s.plan_id = p.id
            WHERE s.status = 'trialing' AND s.trial_ends_at IS NOT NULL AND s.trial_ends_at < ?
        ");
        $stmt_trials->execute([$now]);
        $expired_trials = $stmt_trials->fetchAll();

        foreach ($expired_trials as $t) {
            $fallback_id = $t['fallback_plan_id'];
            if (!$fallback_id) {
                // Find default free plan
                $plans = PlanEngine::getAllPlans();
                foreach ($plans as $p) {
                    if ($p['price'] == 0.00) {
                        $fallback_id = $p['id'];
                        break;
                    }
                }
            }

            if ($fallback_id) {
                $db->prepare("UPDATE subscriptions SET status = 'expired', plan_id = ? WHERE id = ?")->execute([$fallback_id, $t['id']]);
                $logs[] = "Subscription #{$t['id']} (Church #{$t['church_id']}): Trial expired -> Auto-downgraded to Free Plan ID {$fallback_id}";
            }
        }

        // 2. Check expired active periods -> Set to past_due (3-day grace period before downgrade)
        $stmt_active = $db->prepare("
            SELECT id, church_id FROM subscriptions
            WHERE status = 'active' AND current_period_end IS NOT NULL AND current_period_end < ?
        ");
        $stmt_active->execute([$now]);
        $expired_active = $stmt_active->fetchAll();

        foreach ($expired_active as $a) {
            $db->prepare("UPDATE subscriptions SET status = 'past_due' WHERE id = ?")->execute([$a['id']]);
            $logs[] = "Subscription #{$a['id']} (Church #{$a['church_id']}): Period ended -> Status set to past_due";
        }

        // 3. Check past_due subscriptions older than 3 days grace period -> Set to expired
        $grace_cutoff = date('Y-m-d H:i:s', strtotime('-3 days'));
        $stmt_past = $db->prepare("
            SELECT id, church_id FROM subscriptions
            WHERE status = 'past_due' AND current_period_end < ?
        ");
        $stmt_past->execute([$grace_cutoff]);
        $lapsed = $stmt_past->fetchAll();

        foreach ($lapsed as $l) {
            $db->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?")->execute([$l['id']]);
            $logs[] = "Subscription #{$l['id']} (Church #{$l['church_id']}): Grace period lapsed -> Status set to expired";
        }

        return $logs;
    }
}
