import React, { useState, useEffect } from 'react';
import { ActivationStatus, BibleVerse, ThemeConfig } from '@infolayer/shared-types';
import { browserMockNativeAPI, NativeAPI } from './native-api.browser-mock';

export const AppUI: React.FC<{ api?: NativeAPI }> = ({ api = browserMockNativeAPI }) => {
  const [activeTab, setActiveTab] = useState<'console' | 'theme' | 'bible' | 'activation'>('console');
  const [status, setStatus] = useState<ActivationStatus | null>(null);
  const [transcript, setTranscript] = useState<string[]>([]);
  const [liveVerse, setLiveVerse] = useState<string>("John 3:16 - For God so loved the world...");

  // Activation Modal State
  const [inputKey, setInputKey] = useState('');
  const [actError, setActError] = useState('');
  const [actSuccess, setActSuccess] = useState('');

  // Theme Designer State
  const [theme, setTheme] = useState<ThemeConfig>({
    id: 'theme-1',
    name: 'Modern Gold Lower Third',
    fontFamily: 'Inter, sans-serif',
    fontSizePx: 48,
    fontColor: '#FACD32',
    backgroundColor: 'rgba(15, 23, 42, 0.85)',
    alignment: 'center',
    transition: 'fade',
    lowerThird: true
  });

  useEffect(() => {
    api.getActivationStatus().then(setStatus);
    api.onTranscriptEvent((text) => {
      setTranscript(prev => [text, ...prev.slice(0, 9)]);
    });
  }, [api]);

  const handleActivate = async () => {
    setActError('');
    setActSuccess('');
    const res = await api.activateKey(inputKey, 'DEV-MOCK-FINGERPRINT-123');
    if (res.activated) {
      setStatus(res);
      setActSuccess(`Activated successfully on plan: ${res.planName}`);
      setInputKey('');
    } else {
      setActError(res.errorMessage || 'Activation failed');
    }
  };

  return (
    <div style={{ fontFamily: 'sans-serif', background: '#0f172a', color: '#f8fafc', minHeight: '100vh', display: 'flex', flexDirection: 'column' }}>
      {/* Top Bar */}
      <header style={{ background: '#1e293b', padding: '1rem 2rem', borderBottom: '1px solid #334155', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
          <h1 style={{ margin: 0, fontSize: '1.25rem', color: '#38bdf8' }}>InfoLayer Presentation AI</h1>
          <span style={{ fontSize: '0.75rem', padding: '0.2rem 0.5rem', background: '#0284c7', borderRadius: '4px' }}>Tauri + React</span>
        </div>

        {/* Navigation Tabs */}
        <nav style={{ display: 'flex', gap: '1rem' }}>
          <button onClick={() => setActiveTab('console')} style={{ background: activeTab === 'console' ? '#0284c7' : 'transparent', color: '#fff', border: 'none', padding: '0.5rem 1rem', borderRadius: '6px', cursor: 'pointer' }}>Operator Console</button>
          <button onClick={() => setActiveTab('theme')} style={{ background: activeTab === 'theme' ? '#0284c7' : 'transparent', color: '#fff', border: 'none', padding: '0.5rem 1rem', borderRadius: '6px', cursor: 'pointer' }}>Theme Designer</button>
          <button onClick={() => setActiveTab('activation')} style={{ background: activeTab === 'activation' ? '#0284c7' : 'transparent', color: '#fff', border: 'none', padding: '0.5rem 1rem', borderRadius: '6px', cursor: 'pointer' }}>Activation & Key</button>
        </nav>

        {/* Status Pill */}
        <div style={{ fontSize: '0.85rem', color: status?.activated ? '#4ade80' : '#f87171' }}>
          {status?.planName ? `Plan: ${status.planName}` : 'Unactivated'} (14-day Offline Grace Active)
        </div>
      </header>

      {/* Main Content Area */}
      <main style={{ flex: 1, padding: '2rem', maxWidth: '1200px', width: '100%', margin: '0 auto', boxSizing: 'border-box' }}>
        {activeTab === 'console' && (
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '2rem' }}>
            {/* Live Transcript Pane */}
            <div style={{ background: '#1e293b', border: '1px solid #334155', borderRadius: '8px', padding: '1.5rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '1rem' }}>
                <h2 style={{ margin: 0, fontSize: '1.1rem', color: '#38bdf8' }}>Live Audio Transcript (Whisper Engine)</h2>
                <button onClick={() => api.startTranscription()} style={{ background: '#16a34a', color: '#fff', border: 'none', padding: '0.3rem 0.8rem', borderRadius: '4px', cursor: 'pointer' }}>Start Mic</button>
              </div>
              <div style={{ background: '#0f172a', padding: '1rem', borderRadius: '6px', height: '240px', overflowY: 'auto', border: '1px solid #334155' }}>
                {transcript.length === 0 ? (
                  <span style={{ color: '#64748b' }}>Listening for live speech input...</span>
                ) : (
                  transcript.map((t, idx) => (
                    <p key={idx} style={{ margin: '0 0 0.5rem 0', color: idx === 0 ? '#38bdf8' : '#94a3b8' }}>{t}</p>
                  ))
                )}
              </div>
            </div>

            {/* Live Output Preview */}
            <div style={{ background: '#1e293b', border: '1px solid #334155', borderRadius: '8px', padding: '1.5rem' }}>
              <h2 style={{ margin: '0 0 1rem 0', fontSize: '1.1rem', color: '#38bdf8' }}>Audience Screen Output</h2>
              <div style={{
                background: theme.backgroundColor,
                color: theme.fontColor,
                fontFamily: theme.fontFamily,
                fontSize: `${theme.fontSizePx * 0.5}px`,
                padding: '2rem',
                borderRadius: '6px',
                height: '240px',
                display: 'flex',
                alignItems: theme.lowerThird ? 'flex-end' : 'center',
                justifyContent: theme.alignment,
                textAlign: theme.alignment,
                boxShadow: '0 4px 12px rgba(0,0,0,0.5)'
              }}>
                <div>{liveVerse}</div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'theme' && (
          <div style={{ background: '#1e293b', border: '1px solid #334155', borderRadius: '8px', padding: '1.5rem' }}>
            <h2 style={{ margin: '0 0 1rem 0', fontSize: '1.25rem', color: '#38bdf8' }}>No-Code Visual Theme Designer</h2>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '2rem' }}>
              <div>
                <label style={{ display: 'block', margin: '0.5rem 0' }}>Font Color</label>
                <input type="color" value={theme.fontColor} onChange={e => setTheme({ ...theme, fontColor: e.target.value })} style={{ width: '100%', height: '40px', background: '#0f172a', border: '1px solid #334155' }} />

                <label style={{ display: 'block', margin: '0.5rem 0' }}>Background Color</label>
                <input type="text" value={theme.backgroundColor} onChange={e => setTheme({ ...theme, backgroundColor: e.target.value })} style={{ width: '100%', padding: '0.5rem', background: '#0f172a', color: '#fff', border: '1px solid #334155', borderRadius: '4px' }} />

                <label style={{ display: 'block', margin: '0.5rem 0' }}>Text Alignment</label>
                <select value={theme.alignment} onChange={e => setTheme({ ...theme, alignment: e.target.value as any })} style={{ width: '100%', padding: '0.5rem', background: '#0f172a', color: '#fff', border: '1px solid #334155', borderRadius: '4px' }}>
                  <option value="left">Left</option>
                  <option value="center">Center</option>
                  <option value="right">Right</option>
                </select>
              </div>

              <div>
                <h3 style={{ margin: '0 0 0.5rem 0', color: '#cbd5e1' }}>Live Theme Preview</h3>
                <div style={{ background: theme.backgroundColor, color: theme.fontColor, padding: '1.5rem', textAlign: theme.alignment, borderRadius: '6px', minHeight: '150px', display: 'flex', alignItems: 'center', justifyContent: theme.alignment }}>
                  For God so loved the world, that he gave his only begotten Son...
                </div>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'activation' && (
          <div style={{ background: '#1e293b', border: '1px solid #334155', borderRadius: '8px', padding: '1.5rem', maxWidth: '600px', margin: '0 auto' }}>
            <h2 style={{ margin: '0 0 1rem 0', fontSize: '1.25rem', color: '#38bdf8' }}>Activation & Feature Unlock</h2>
            <p style={{ color: '#94a3b8', fontSize: '0.9rem' }}>Enter your Activation Code to connect this Church PC and sync your plan feature flags from the InfoLayer Portal.</p>

            {actError && <div style={{ background: '#7f1d1d', color: '#fca5a5', padding: '0.75rem', borderRadius: '6px', marginBottom: '1rem' }}>{actError}</div>}
            {actSuccess && <div style={{ background: '#14532d', color: '#86efac', padding: '0.75rem', borderRadius: '6px', marginBottom: '1rem' }}>{actSuccess}</div>}

            <input
              type="text"
              placeholder="e.g. INFOLAYER-TEST-KEY-12345"
              value={inputKey}
              onChange={e => setInputKey(e.target.value)}
              style={{ width: '100%', padding: '0.75rem', background: '#0f172a', border: '1px solid #334155', borderRadius: '6px', color: '#fff', boxSizing: 'border-box', marginBottom: '1rem' }}
            />
            <button onClick={handleActivate} style={{ width: '100%', padding: '0.75rem', background: '#0284c7', color: '#fff', border: 'none', borderRadius: '6px', fontWeight: 'bold', cursor: 'pointer' }}>
              Submit Activation Code
            </button>
          </div>
        )}
      </main>
    </div>
  );
};
