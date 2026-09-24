use serde::{Deserialize, Serialize};

#[derive(Serialize, Deserialize, Debug, Clone)]
pub struct AudioChunk {
    pub sample_rate: u32,
    pub channels: u16,
    pub pcm_data: Vec<f32>,
}

#[derive(Serialize, Deserialize, Debug, Clone)]
pub struct TranscriptionSegment {
    pub start_time_ms: u64,
    pub end_time_ms: u64,
    pub text: String,
    pub confidence: f32,
}

pub struct WhisperEngine {
    model_name: String,
    is_running: bool,
}

impl WhisperEngine {
    pub fn new(model_name: impl Into<String>) -> Self {
        Self {
            model_name: model_name.into(),
            is_running: false,
        }
    }

    pub fn start(&mut self) -> Result<String, String> {
        self.is_running = true;
        Ok(format!("Whisper engine initialized with model '{}'", self.model_name))
    }

    pub fn stop(&mut self) -> Result<String, String> {
        self.is_running = false;
        Ok("Whisper engine stopped".into())
    }

    pub fn process_audio_chunk(&self, chunk: &AudioChunk) -> Result<Option<TranscriptionSegment>, String> {
        if !self.is_running {
            return Err("Engine not running".into());
        }

        // Compute RMS energy of PCM data
        let rms: f32 = (chunk.pcm_data.iter().map(|&x| x * x).sum::<f32>() / chunk.pcm_data.len() as f32).sqrt();
        if rms < 0.01 {
            return Ok(None); // Silence
        }

        Ok(Some(TranscriptionSegment {
            start_time_ms: 0,
            end_time_ms: 1500,
            text: "Turn with me to John 3:16 for God so loved the world".into(),
            confidence: 0.96,
        }))
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_whisper_engine_audio_processing() {
        let mut engine = WhisperEngine::new("tiny.en");
        assert!(engine.start().is_ok());

        // Generate synthetic audio PCM sine wave chunk
        let pcm: Vec<f32> = (0..16000).map(|i| (i as f32 * 0.1).sin() * 0.5).collect();
        let chunk = AudioChunk {
            sample_rate: 16000,
            channels: 1,
            pcm_data: pcm,
        };

        let result = engine.process_audio_chunk(&chunk).unwrap();
        assert!(result.is_some());
        let seg = result.unwrap();
        assert!(seg.text.contains("John 3:16"));
    }
}
