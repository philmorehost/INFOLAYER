use serde::{Deserialize, Serialize};
use std::fs;
use std::path::PathBuf;

#[derive(Serialize, Deserialize, Debug, Clone, PartialEq)]
pub struct PlanFeature {
    pub max_devices: u32,
    pub transcription_minutes: i32,
    pub output_types: Vec<String>,
    pub ai_features_enabled: bool,
    pub watermark: bool,
    pub theme_designer_access: String,
}

#[derive(Serialize, Deserialize, Debug, Clone)]
pub struct ActivationResponse {
    pub success: bool,
    pub message: Option<String>,
    pub error: Option<String>,
    pub activation_code: Option<String>,
    pub subscription_status: Option<String>,
    pub plan_name: Option<String>,
    pub plan_type: Option<String>,
    pub features: Option<PlanFeature>,
}

#[derive(Serialize, Deserialize, Debug, Clone, PartialEq)]
pub struct LocalActivationCache {
    pub activated: bool,
    pub activation_code: String,
    pub plan_name: String,
    pub last_validated: String,
    pub grace_days_remaining: u32,
    pub features: PlanFeature,
}

pub struct ActivationClient {
    portal_url: String,
}

impl ActivationClient {
    pub fn new(portal_url: String) -> Self {
        Self { portal_url }
    }

    pub async fn activate_key(&self, code: &str, fingerprint: &str) -> Result<ActivationResponse, String> {
        let client = reqwest::Client::new();
        let url = format!("{}/api/activate", self.portal_url);

        let params = serde_json::json!({
            "activation_code": code,
            "device_fingerprint": fingerprint,
            "device_name": "Church PC - Tauri App"
        });

        match client.post(&url).json(&params).send().await {
            Ok(resp) => {
                let status_res = resp.json::<ActivationResponse>().await;
                status_res.map_err(|e| format!("Failed to parse response: {}", e))
            }
            Err(e) => Err(format!("HTTPS request failed: {}", e)),
        }
    }

    pub fn save_local_cache(cache: &LocalActivationCache) -> Result<(), String> {
        let cache_path = Self::get_cache_path();
        let data = serde_json::to_string(cache).map_err(|e| e.to_string())?;
        fs::write(cache_path, data).map_err(|e| e.to_string())
    }

    pub fn load_local_cache() -> Option<LocalActivationCache> {
        let cache_path = Self::get_cache_path();
        if cache_path.exists() {
            if let Ok(content) = fs::read_to_string(cache_path) {
                return serde_json::from_str(&content).ok();
            }
        }
        None
    }

    fn get_cache_path() -> PathBuf {
        std::env::temp_dir().join("infolayer_activation_cache.json")
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_cache_serialization_roundtrip() {
        let cache = LocalActivationCache {
            activated: true,
            activation_code: "INFOLAYER-TEST-123".into(),
            plan_name: "Pro Tier".into(),
            last_validated: "2025-01-01T00:00:00Z".into(),
            grace_days_remaining: 14,
            features: PlanFeature {
                max_devices: 2,
                transcription_minutes: -1,
                output_types: vec!["hdmi".into(), "ndi".into()],
                ai_features_enabled: true,
                watermark: false,
                theme_designer_access: "full".into(),
            },
        };

        assert!(ActivationClient::save_local_cache(&cache).is_ok());
        let loaded = ActivationClient::load_local_cache();
        assert!(loaded.is_some());
        assert_eq!(loaded.unwrap(), cache);
    }
}
