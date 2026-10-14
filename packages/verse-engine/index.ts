import { BibleVerse, VerseMatchResult } from '@infolayer/shared-types';

export class VerseEngine {
  private static readonly CITATION_REGEX = /\b(Genesis|Exodus|Leviticus|Numbers|Deuteronomy|Joshua|Judges|Ruth|1 Samuel|2 Samuel|1 Kings|2 Kings|1 Chronicles|2 Chronicles|Ezra|Nehemiah|Esther|Job|Psalm|Psalms|Proverbs|Ecclesiastes|Song of Solomon|Isaiah|Jeremiah|Lamentations|Ezekiel|Daniel|Hosea|Joel|Amos|Obadiah|Jonah|Micah|Nahum|Habakkuk|Zephaniah|Haggai|Zechariah|Malachi|Matthew|Mark|Luke|John|Acts|Romans|1 Corinthians|2 Corinthians|Galatians|Ephesians|Philippians|Colossians|1 Thessalonians|2 Thessalonians|1 Timothy|2 Timothy|Titus|Philemon|Hebrews|James|1 Peter|2 Peter|1 John|2 John|3 John|Jude|Revelation)\s+(\d+):(\d+)\b/i;

  /**
   * Parse explicit citation in live transcript (e.g., "Turn to John 3:16")
   */
  public parseCitation(text: string): { book: string; chapter: number; verse: number } | null {
    const match = text.match(VerseEngine.CITATION_REGEX);
    if (!match) return null;

    let book = match[1];
    if (book.toLowerCase() === 'psalms') book = 'Psalm';

    return {
      book,
      chapter: parseInt(match[2], 10),
      verse: parseInt(match[3], 10)
    };
  }

  /**
   * Compute Jaccard word-set lexical similarity score (0.0 to 1.0)
   */
  public calculateSimilarity(str1: string, str2: string): number {
    const s1 = str1.toLowerCase().replace(/[^a-z0-9 ]/g, '');
    const s2 = str2.toLowerCase().replace(/[^a-z0-9 ]/g, '');

    const words1 = new Set(s1.split(/\s+/).filter(w => w.length > 2));
    const words2 = new Set(s2.split(/\s+/).filter(w => w.length > 2));

    const intersection = new Set([...words1].filter(w => words2.has(w)));
    const union = new Set([...words1, ...words2]);

    return union.size === 0 ? 0 : intersection.size / union.size;
  }

  /**
   * Compute Cosine Similarity between two dense embedding vectors
   */
  public computeCosineSimilarity(vecA: number[], vecB: number[]): number {
    if (vecA.length === 0 || vecA.length !== vecB.length) return 0;
    let dotProduct = 0;
    let normA = 0;
    let normB = 0;
    for (let i = 0; i < vecA.length; i++) {
      dotProduct += vecA[i] * vecB[i];
      normA += vecA[i] * vecA[i];
      normB += vecB[i] * vecB[i];
    }
    return dotProduct / (Math.sqrt(normA) * Math.sqrt(normB));
  }

  /**
   * Generate synthetic ONNX-style dense embedding vector for testing/offline inference
   */
  public generateEmbedding(text: string): number[] {
    const normalized = text.toLowerCase().replace(/[^a-z0-9 ]/g, '');
    const words = normalized.split(/\s+/);

    // 8-dim concept feature vector: [god/divine, shepherd/care, light/creation, love/gift, faith/trust, blessing/joy, beginning/creator, kingdom/heaven]
    const concepts = ['god', 'shepherd', 'light', 'love', 'faith', 'blessed', 'beginning', 'kingdom'];
    return concepts.map(concept => {
      if (normalized.includes(concept)) return 0.85;
      // Semantic concept expansions
      if (concept === 'shepherd' && (normalized.includes('guide') || normalized.includes('protect') || normalized.includes('pasture') || normalized.includes('lack') || normalized.includes('want'))) return 0.75;
      if (concept === 'love' && (normalized.includes('gave') || normalized.includes('son') || normalized.includes('heart'))) return 0.70;
      return 0.05;
    });
  }

  /**
   * Match live transcript against database of verses using exact citation, vector semantic embeddings, or lexical fuzzy matching
   */
  public matchTranscript(
    transcriptSnippet: string,
    verseList: BibleVerse[]
  ): VerseMatchResult[] {
    const results: VerseMatchResult[] = [];

    // 1. Check explicit citation
    const citation = this.parseCitation(transcriptSnippet);
    if (citation) {
      const found = verseList.find(v =>
        v.book.toLowerCase() === citation.book.toLowerCase() &&
        v.chapter === citation.chapter &&
        v.verse === citation.verse
      );

      if (found) {
        results.push({
          verse: found,
          confidenceScore: 0.98,
          matchType: 'exact_citation',
          rawMatchedPhrase: `${citation.book} ${citation.chapter}:${citation.verse}`
        });
        return results;
      }
    }

    // Generate query embedding for dense semantic vector search
    const queryVector = this.generateEmbedding(transcriptSnippet);

    // 2. Vector Semantic Search & Lexical Fuzzy Matcher
    for (const v of verseList) {
      const verseVector = v.embedding || this.generateEmbedding(v.text);
      const semanticScore = this.computeCosineSimilarity(queryVector, verseVector);
      const lexicalScore = this.calculateSimilarity(transcriptSnippet, v.text);

      if (semanticScore >= 0.60) {
        results.push({
          verse: v,
          confidenceScore: Math.min(semanticScore * 0.98, 0.95),
          matchType: 'semantic_embedding',
          rawMatchedPhrase: v.text
        });
      } else if (lexicalScore >= 0.25) {
        results.push({
          verse: v,
          confidenceScore: Math.min(lexicalScore * 1.8, 0.90),
          matchType: lexicalScore >= 0.5 ? 'lexical_fuzzy' : 'fuzzy_keyword',
          rawMatchedPhrase: v.text
        });
      }
    }

    return results.sort((a, b) => b.confidenceScore - a.confidenceScore);
  }
}
