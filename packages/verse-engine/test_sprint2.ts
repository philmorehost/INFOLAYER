import { VerseEngine } from './index';
import { BibleVerse } from '@infolayer/shared-types';

console.log("=== SPRINT 2 & 3 VERSE ENGINE VERIFICATION TEST RUN ===");

const engine = new VerseEngine();

// Test 1: Citation Parsing
const transcript1 = "Welcome everyone, please open your Bibles to John 3:16 as we begin.";
const citation = engine.parseCitation(transcript1);
console.log("Parsed Citation:", citation);
if (citation && citation.book === 'John' && citation.chapter === 3 && citation.verse === 16) {
  console.log("[PASS] Citation parsing test passed.");
} else {
  console.error("[FAIL] Citation parsing failed.");
  process.exit(1);
}

// Test 2: Paraphrase Semantic Vector Matching
const mockVerses: BibleVerse[] = [
  { id: 1, translation: 'KJV', book: 'John', chapter: 3, verse: 16, text: 'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.' },
  { id: 2, translation: 'KJV', book: 'Psalm', chapter: 23, verse: 1, text: 'The LORD is my shepherd; I shall not want.' }
];

const paraphraseQuery = "The Lord is like a shepherd to me, I will never lack anything";
const semanticMatches = engine.matchTranscript(paraphraseQuery, mockVerses);

console.log("\nParaphrase Query:", `"${paraphraseQuery}"`);
console.log("Semantic Match Results:", semanticMatches);

if (semanticMatches.length > 0 && semanticMatches[0].verse.book === 'Psalm' && semanticMatches[0].matchType === 'semantic_embedding') {
  console.log("[PASS] Local ONNX vector semantic paraphrase matching passed.");
} else {
  console.error("[FAIL] Paraphrase semantic vector matching failed.");
  process.exit(1);
}

console.log("\n=== ALL VERSE ENGINE TESTS PASSED SUCCESSFULLY ===");
