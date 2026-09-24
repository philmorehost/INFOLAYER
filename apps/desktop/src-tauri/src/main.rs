use infolayer_core::broadcast::{NdiConfig, NdiOutput, VirtualCamOutput};
use infolayer_core::whisper_engine::WhisperEngine;
use infolayer_core::{ActivationClient, LocalActivationCache, PlanFeature};
use serde::{Deserialize, Serialize};
use std::sync::Mutex;
use tauri::State;

struct AppState {
    portal_url: Mutex<String>,
}

#[derive(Serialize, Deserialize, Debug)]
pub struct DisplayInfo {
    pub id: String,
    pub name: String,
    pub is_primary: bool,
}

#[tauri::command]
fn get_displays() -> Vec<DisplayInfo> {
    vec![
        DisplayInfo {
            id: "1".into(),
            name: "Primary Monitor (Operator)".into(),
            is_primary: true,
        },
        DisplayInfo {
            id: "2".into(),
            name: "HDMI Projector Output".into(),
            is_primary: false,
        },
        DisplayInfo {
            id: "3".into(),
            name: "Stage Confidence Monitor".into(),
            is_primary: false,
        },
    ]
}

#[tauri::command]
async fn activate_key(
    code: String,
    fingerprint: String,
    state: State<'_, AppState>,
) -> Result<serde_json::Value, String> {
    let portal_url = state.portal_url.lock().unwrap().clone();
    let client = ActivationClient::new(portal_url);

    match client.activate_key(&code, &fingerprint).await {
        Ok(res) => {
            if res.success {
                let cache = LocalActivationCache {
                    activated: true,
                    activation_code: code,
                    plan_name: res.plan_name.clone().unwrap_or_else(|| "Custom Plan".into()),
                    last_validated: "2025-01-01T00:00:00Z".into(),
                    grace_days_remaining: 14,
                    features: res.features.clone().unwrap_or(PlanFeature {
                        max_devices: 1,
                        transcription_minutes: -1,
                        output_types: vec!["hdmi".into()],
                        ai_features_enabled: false,
                        watermark: false,
                        theme_designer_access: "full".into(),
                    }),
                };
                let _ = ActivationClient::save_local_cache(&cache);
                Ok(serde_json::to_value(res).unwrap())
            } else {
                Err(res.error.unwrap_or_else(|| "Activation failed".into()))
            }
        }
        Err(err) => Err(err),
    }
}

#[tauri::command]
fn get_activation_status() -> serde_json::Value {
    if let Some(cache) = ActivationClient::load_local_cache() {
        serde_json::json!({
            "activated": cache.activated,
            "activationCode": cache.activation_code,
            "planName": cache.plan_name,
            "features": cache.features,
            "offlineGracePeriodDaysRemaining": cache.grace_days_remaining
        })
    } else {
        serde_json::json!({
            "activated": true,
            "planName": "Free Explorer (Offline Core)",
            "features": {
                "max_devices": 1,
                "transcription_minutes": 60,
                "output_types": ["hdmi"],
                "ai_features_enabled": false,
                "watermark": false,
                "theme_designer_access": "basic"
            },
            "offlineGracePeriodDaysRemaining": 14
        })
    }
}

#[tauri::command]
fn start_whisper_stt() -> String {
    let mut engine = WhisperEngine::new("tiny.en");
    engine.start().unwrap_or_else(|e| e)
}

#[tauri::command]
fn start_ndi_stream(stream_name: String) -> String {
    let config = NdiConfig {
        stream_name,
        width: 1920,
        height: 1080,
        fps: 60,
    };
    let mut ndi = NdiOutput::new(config);
    ndi.start().unwrap_or_else(|e| e)
}

#[tauri::command]
fn start_virtual_camera(device_name: String) -> String {
    let mut vcam = VirtualCamOutput::new(device_name);
    vcam.start().unwrap_or_else(|e| e)
}

fn main() {
    tauri::Builder::default()
        .manage(AppState {
            portal_url: Mutex::new("http://localhost:8000".into()),
        })
        .invoke_handler(tauri::generate_handler![
            get_displays,
            activate_key,
            get_activation_status,
            start_whisper_stt,
            start_ndi_stream,
            start_virtual_camera
        ])
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
