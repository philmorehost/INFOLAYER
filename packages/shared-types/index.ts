export interface PlanFeature {
  max_devices: number;
  transcription_minutes: number; // -1 for unlimited
  output_types: ('hdmi' | 'ndi' | 'virtual_cam')[];
  ai_features_enabled: boolean;
  watermark: boolean;
  theme_designer_access: 'none' | 'basic' | 'full';
}

export interface CustomPlan {
  id: number;
  name: string;
  description: string;
  type: 'trial' | 'paid';
  billing_cycle: 'monthly' | 'quarterly' | 'yearly' | 'one_time' | 'trial_days';
  price: number;
  currency: string;
  trial_days?: number;
  trial_usage_cap_minutes?: number;
  fallback_plan_id?: number;
  is_active: boolean;
  sort_order: number;
  features: PlanFeature;
}

export interface ActivationStatus {
  activated: boolean;
  activationCode?: string;
  planName?: string;
  planType?: 'trial' | 'paid';
  features?: PlanFeature;
  offlineGracePeriodDaysRemaining?: number;
  lastValidatedAt?: string;
  errorMessage?: string;
}

export interface BibleVerse {
  id: number;
  translation: string;
  book: string;
  chapter: number;
  verse: number;
  text: string;
}

export interface SongSlide {
  id: string;
  title: string;
  lyrics: string[];
}

export interface ThemeConfig {
  id: string;
  name: string;
  fontFamily: string;
  fontSizePx: number;
  fontColor: string;
  backgroundColor: string;
  backgroundImageUrl?: string;
  alignment: 'left' | 'center' | 'right';
  transition: 'fade' | 'slide' | 'zoom' | 'none';
  lowerThird: boolean;
}

export interface PlaylistItem {
  id: string;
  type: 'verse' | 'song' | 'slide' | 'custom';
  title: string;
  content: string;
  themeId?: string;
}

export interface VerseMatchResult {
  verse: BibleVerse;
  confidenceScore: number; // 0.0 to 1.0
  matchType: 'exact_citation' | 'fuzzy_keyword' | 'lexical_fuzzy';
  rawMatchedPhrase: string;
}
