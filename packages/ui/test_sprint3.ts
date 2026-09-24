import React from 'react';
import { renderToString } from 'react-dom/server';
import { AppUI } from './App';
import { browserMockNativeAPI } from './native-api.browser-mock';

console.log("=== SPRINT 3 VERIFICATION TEST RUN ===");

// 1. Test Browser Mock API
browserMockNativeAPI.getActivationStatus().then(status => {
  console.log("Mock Activation Status:", status);
  if (status.activated && status.planName) {
    console.log("[PASS] Browser mock native bridge working.");
  } else {
    console.error("[FAIL] Browser mock native bridge failed.");
    process.exit(1);
  }
});

// 2. Test SSR Render of React App UI
try {
  const html = renderToString(React.createElement(AppUI, { api: browserMockNativeAPI }));
  if (html.includes("InfoLayer Presentation AI") && html.includes("Live Audio Transcript")) {
    console.log("[PASS] React UI component rendered to HTML successfully.");
  } else {
    console.error("[FAIL] React UI component render missing expected content.");
    process.exit(1);
  }
} catch (err) {
  console.error("[FAIL] React UI render threw error:", err);
  process.exit(1);
}

setTimeout(() => {
  console.log("=== SPRINT 3 ALL TESTS PASSED SUCCESSFULLY ===");
}, 500);
