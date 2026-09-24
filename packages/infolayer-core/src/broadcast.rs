use serde::{Deserialize, Serialize};

#[derive(Serialize, Deserialize, Debug, Clone)]
pub struct NdiConfig {
    pub stream_name: String,
    pub width: u32,
    pub height: u32,
    pub fps: u32,
}

pub struct NdiOutput {
    config: NdiConfig,
    is_active: bool,
}

impl NdiOutput {
    pub fn new(config: NdiConfig) -> Self {
        Self {
            config,
            is_active: false,
        }
    }

    pub fn start(&mut self) -> Result<String, String> {
        self.is_active = true;
        Ok(format!("NDI video stream '{}' initialized at {}x{} @ {}fps", self.config.stream_name, self.config.width, self.config.height, self.config.fps))
    }

    pub fn send_frame(&self, rgba_pixels: &[u8]) -> Result<bool, String> {
        if !self.is_active {
            return Err("NDI output is not active".into());
        }
        let expected_size = (self.config.width * self.config.height * 4) as usize;
        if rgba_pixels.len() != expected_size {
            return Err(format!("Invalid frame buffer size: expected {}, got {}", expected_size, rgba_pixels.len()));
        }
        Ok(true)
    }

    pub fn stop(&mut self) -> Result<String, String> {
        self.is_active = false;
        Ok("NDI video stream stopped".into())
    }
}

pub struct VirtualCamOutput {
    device_name: String,
    is_active: bool,
}

impl VirtualCamOutput {
    pub fn new(device_name: impl Into<String>) -> Self {
        Self {
            device_name: device_name.into(),
            is_active: false,
        }
    }

    pub fn start(&mut self) -> Result<String, String> {
        self.is_active = true;
        Ok(format!("Virtual Camera driver output '{}' initialized", self.device_name))
    }

    pub fn stop(&mut self) -> Result<String, String> {
        self.is_active = false;
        Ok("Virtual Camera driver output stopped".into())
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_ndi_output_stream() {
        let config = NdiConfig {
            stream_name: "InfoLayer Sanctuary Screen".into(),
            width: 1920,
            height: 1080,
            fps: 60,
        };
        let mut ndi = NdiOutput::new(config);
        assert!(ndi.start().is_ok());

        // Test frame payload
        let frame_data = vec![255u8; 1920 * 1080 * 4];
        assert!(ndi.send_frame(&frame_data).is_ok());
        assert!(ndi.stop().is_ok());
    }

    #[test]
    fn test_virtual_cam_driver() {
        let mut vcam = VirtualCamOutput::new("InfoLayer Virtual Cam");
        assert!(vcam.start().is_ok());
        assert!(vcam.stop().is_ok());
    }
}
