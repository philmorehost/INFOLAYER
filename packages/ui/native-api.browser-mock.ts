import { ActivationStatus, CustomPlan } from '@infolayer/shared-types';

export interface NativeAPI {
  // Display Management
  getDisplays: () => Promise<{ id: string; name: string; isPrimary: boolean }[]>;
  openWindow: (windowType: 'stage' | 'output') => Promise<boolean>;

  // Activation Bridge
  activateKey: (code: string, fingerprint: string) => Promise<ActivationStatus>;
  getActivationStatus: () => Promise<ActivationStatus>;
  deactivateDevice: (code: string, fingerprint: string) => Promise<boolean>;

  // STT / Audio
  startTranscription: () => Promise<boolean>;
  stopTranscription: () => Promise<boolean>;
  onTranscriptEvent: (callback: (text: string) => void) => void;
}

// Browser Mock Bridge implementation for standalone browser testing
export const browserMockNativeAPI: NativeAPI = {
  getDisplays: async () => [
    { id: '1', name: 'Primary Monitor (Operator)', isPrimary: true },
    { id: '2', name: 'HDMI Projector Output', isPrimary: false },
    { id: '3', name: 'Stage Confidence Monitor', isPrimary: false }
  ],
  openWindow: async (type) => {
    console.log(`[Browser Mock] Opening window: ${type}`);
    return true;
  },
  activateKey: async (code, fingerprint) => {
    if (code.startsWith('INFOLAYER')) {
      return {
        activated: true,
        activationCode: code,
        planName: 'Church Pro (Mock)',
        planType: 'paid',
        features: {
          max_devices: 3,
          transcription_minutes: -1,
          output_types: ['hdmi', 'ndi', 'virtual_cam'],
          ai_features_enabled: true,
          watermark: false,
          theme_designer_access: 'full'
        },
        offlineGracePeriodDaysRemaining: 14
      };
    }
    return {
      activated: false,
      errorMessage: 'Invalid activation code entered in mock bridge.'
    };
  },
  getActivationStatus: async () => ({
    activated: true,
    activationCode: 'INFOLAYER-MOCK-ACTIVE-KEY',
    planName: 'Free Explorer (Offline Core)',
    planType: 'trial',
    features: {
      max_devices: 1,
      transcription_minutes: 60,
      output_types: ['hdmi'],
      ai_features_enabled: false,
      watermark: false,
      theme_designer_access: 'basic'
    },
    offlineGracePeriodDaysRemaining: 14
  }),
  deactivateDevice: async () => true,
  startTranscription: async () => true,
  stopTranscription: async () => true,
  onTranscriptEvent: (callback) => {
    // Simulate live speech events
    setTimeout(() => callback("Turn with me to John 3:16 for God so loved the world..."), 2000);
    setTimeout(() => callback("The LORD is my shepherd; I shall not want."), 6000);
  }
};
