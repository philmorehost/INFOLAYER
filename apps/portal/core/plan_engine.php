<?php
/**
 * Plan Engine & Feature Flag Service
 */

require_once __DIR__ . '/db.php';

class PlanEngine {

    /**
     * Create or update a plan along with its feature flags
     */
    public static function savePlan(array $data, array $features): int {
        $db = PortalDB::getConnection();

        if (!empty($data['id'])) {
            $stmt = $db->prepare("
                UPDATE plans SET
                    name = :name,
                    description = :description,
                    type = :type,
                    billing_cycle = :billing_cycle,
                    price = :price,
                    currency = :currency,
                    trial_days = :trial_days,
                    trial_usage_cap_minutes = :trial_usage_cap_minutes,
                    fallback_plan_id = :fallback_plan_id,
                    is_active = :is_active,
                    sort_order = :sort_order
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $data['id'],
                ':name' => $data['name'],
                ':description' => $data['description'] ?? '',
                ':type' => $data['type'],
                ':billing_cycle' => $data['billing_cycle'],
                ':price' => $data['price'],
                ':currency' => $data['currency'] ?? 'USD',
                ':trial_days' => $data['trial_days'] ?? null,
                ':trial_usage_cap_minutes' => $data['trial_usage_cap_minutes'] ?? null,
                ':fallback_plan_id' => $data['fallback_plan_id'] ?? null,
                ':is_active' => $data['is_active'] ?? 1,
                ':sort_order' => $data['sort_order'] ?? 0,
            ]);
            $plan_id = (int)$data['id'];
        } else {
            $stmt = $db->prepare("
                INSERT INTO plans (
                    name, description, type, billing_cycle, price, currency,
                    trial_days, trial_usage_cap_minutes, fallback_plan_id, is_active, sort_order
                ) VALUES (
                    :name, :description, :type, :billing_cycle, :price, :currency,
                    :trial_days, :trial_usage_cap_minutes, :fallback_plan_id, :is_active, :sort_order
                )
            ");
            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'] ?? '',
                ':type' => $data['type'],
                ':billing_cycle' => $data['billing_cycle'],
                ':price' => $data['price'],
                ':currency' => $data['currency'] ?? 'USD',
                ':trial_days' => $data['trial_days'] ?? null,
                ':trial_usage_cap_minutes' => $data['trial_usage_cap_minutes'] ?? null,
                ':fallback_plan_id' => $data['fallback_plan_id'] ?? null,
                ':is_active' => $data['is_active'] ?? 1,
                ':sort_order' => $data['sort_order'] ?? 0,
            ]);
            $plan_id = (int)$db->lastInsertId();
        }

        // Save feature flags
        $stmt_del = $db->prepare("DELETE FROM plan_features WHERE plan_id = ?");
        $stmt_del->execute([$plan_id]);

        $stmt_feat = $db->prepare("INSERT INTO plan_features (plan_id, feature_key, feature_value) VALUES (?, ?, ?)");
        foreach ($features as $key => $val) {
            $stmt_feat->execute([$plan_id, $key, json_encode($val)]);
        }

        return $plan_id;
    }

    /**
     * Get plan details with feature flags
     */
    public static function getPlan(int $plan_id): ?array {
        $db = PortalDB::getConnection();
        $stmt = $db->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch();
        if (!$plan) return null;

        $stmt_feat = $db->prepare("SELECT feature_key, feature_value FROM plan_features WHERE plan_id = ?");
        $stmt_feat->execute([$plan_id]);
        $features = [];
        while ($row = $stmt_feat->fetch()) {
            $features[$row['feature_key']] = json_decode($row['feature_value'], true);
        }

        $plan['features'] = $features;
        return $plan;
    }

    /**
     * List all plans
     */
    public static function getAllPlans(bool $active_only = false): array {
        $db = PortalDB::getConnection();
        $sql = "SELECT * FROM plans " . ($active_only ? "WHERE is_active = 1 " : "") . "ORDER BY sort_order ASC, id ASC";
        $stmt = $db->query($sql);
        $plans = $stmt->fetchAll();

        foreach ($plans as &$p) {
            $stmt_feat = $db->prepare("SELECT feature_key, feature_value FROM plan_features WHERE plan_id = ?");
            $stmt_feat->execute([$p['id']]);
            $features = [];
            while ($row = $stmt_feat->fetch()) {
                $features[$row['feature_key']] = json_decode($row['feature_value'], true);
            }
            $p['features'] = $features;
        }
        return $plans;
    }
}
