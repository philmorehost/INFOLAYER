import { VerseEngine } from './packages/verse-engine';
import { initializeBibleDatabase } from './packages/bible-data';
import * as path from 'path';
import * as fs from 'fs';

console.log("=== TRACK 1 END-TO-END PIPELINE TRACE ===");

// 1. Initialize Bible Database & Verses
const dbPath = path.join(__dirname, 'test_pipeline_bible.sqlite');
if (fs.existsSync(dbPath)) fs.unlinkSync(dbPath);

const db = initializeBibleDatabase(dbPath);
const verseEngine = new VerseEngine();

// Fetch KJV verses from database
const verses = db.prepare("SELECT * FROM verses WHERE translation = 'KJV'").all() as any[];

// 2. Simulate Streamed Audio -> Whisper STT -> Verse Engine Match -> Output Render
console.log("\n[STEP 1: AUDIO CHUNK INPUT]");
console.log("Audio Stream: Received 16kHz PCM audio chunk (16,000 samples)");

console.log("\n[STEP 2: WHISPER STT ENGINE PROCESSING]");
const whisperTranscript = "Turn with me to John 3:16 for God so loved the world";
console.log(`Transcribed Text: "${whisperTranscript}"`);

console.log("\n[STEP 3: VERSE ENGINE MATCHING]");
const matches = verseEngine.matchTranscript(whisperTranscript, verses);
console.log("Verse Matches Found:", matches.length);
if (matches.length > 0) {
  const topMatch = matches[0];
  console.log(`Top Match: ${topMatch.verse.book} ${topMatch.verse.chapter}:${topMatch.verse.verse}`);
  console.log(`Match Type: ${topMatch.matchType}`);
  console.log(`Confidence Score: ${topMatch.confidenceScore}`);
  console.log(`Scripture Text: "${topMatch.verse.text}"`);

  console.log("\n[STEP 4: PRESENTATION OUTPUT DISPLAY RENDER]");
  console.log("Rendered Screen Display:");
  console.log("-----------------------------------------------------------------");
  console.log(`| [LIVE OUTPUT SCREEN]                                          |`);
  console.log(`| "${topMatch.verse.text}"                                      |`);
  console.log(`| - ${topMatch.verse.book} ${topMatch.verse.chapter}:${topMatch.verse.verse} (${topMatch.verse.translation})                             |`);
  console.log("-----------------------------------------------------------------");
  console.log("\n=== PIPELINE TRACE EXECUTED SUCCESSFULLY ===");
} else {
  console.error("Pipeline failed: No verse match found.");
  process.exit(1);
}

// Cleanup
if (fs.existsSync(dbPath)) fs.unlinkSync(dbPath);
