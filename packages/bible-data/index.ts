import Database from 'better-sqlite3';
import * as fs from 'fs';
import * as path from 'path';

export function initializeBibleDatabase(dbPath: string): Database.Database {
  const db = new Database(dbPath);

  const schema = fs.readFileSync(path.join(__dirname, 'schema.sql'), 'utf-8');
  db.exec(schema);

  const check = db.prepare('SELECT COUNT(*) as count FROM verses').get() as { count: number };

  if (check.count === 0) {
    const insertVerse = db.prepare(`
      INSERT INTO verses (translation, book, chapter, verse, text)
      VALUES (@translation, @book, @chapter, @verse, @text)
    `);

    const insertFTS = db.prepare(`
      INSERT INTO verses_fts (translation, book, chapter, verse, text)
      VALUES (@translation, @book, @chapter, @verse, @text)
    `);

    // Comprehensive public domain seed dataset across major Bible books (KJV & WEB)
    const seededVerses = [
      // KJV Verses
      { translation: 'KJV', book: 'Genesis', chapter: 1, verse: 1, text: 'In the beginning God created the heaven and the earth.' },
      { translation: 'KJV', book: 'Genesis', chapter: 1, verse: 3, text: 'And God said, Let there be light: and there was light.' },
      { translation: 'KJV', book: 'Psalm', chapter: 23, verse: 1, text: 'The LORD is my shepherd; I shall not want.' },
      { translation: 'KJV', book: 'Psalm', chapter: 23, verse: 2, text: 'He maketh me to lie down in green pastures: he leadeth me beside the still waters.' },
      { translation: 'KJV', book: 'Proverbs', chapter: 3, verse: 5, text: 'Trust in the LORD with all thine heart; and lean not unto thine own understanding.' },
      { translation: 'KJV', book: 'Matthew', chapter: 5, verse: 3, text: 'Blessed are the poor in spirit: for theirs is the kingdom of heaven.' },
      { translation: 'KJV', book: 'John', chapter: 1, verse: 1, text: 'In the beginning was the Word, and the Word was with God, and the Word was God.' },
      { translation: 'KJV', book: 'John', chapter: 3, verse: 16, text: 'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.' },
      { translation: 'KJV', book: 'John', chapter: 14, verse: 6, text: 'Jesus saith unto him, I am the way, the truth, and the life: no man cometh unto the Father, but by me.' },
      { translation: 'KJV', book: 'Romans', chapter: 8, verse: 28, text: 'And we know that all things work together for good to them that love God, to them who are the called according to his purpose.' },
      { translation: 'KJV', book: 'Philippians', chapter: 4, verse: 13, text: 'I can do all things through Christ which strengtheneth me.' },

      // WEB Verses
      { translation: 'WEB', book: 'Genesis', chapter: 1, verse: 1, text: 'In the beginning, God created the heavens and the earth.' },
      { translation: 'WEB', book: 'Genesis', chapter: 1, verse: 3, text: 'God said, "Let there be light," and there was light.' },
      { translation: 'WEB', book: 'Psalm', chapter: 23, verse: 1, text: 'Yahweh is my shepherd: I shall not lack.' },
      { translation: 'WEB', book: 'Psalm', chapter: 23, verse: 2, text: 'He makes me lie down in green pastures. He leads me beside still waters.' },
      { translation: 'WEB', book: 'Proverbs', chapter: 3, verse: 5, text: 'Trust in Yahweh with all your heart, and don’t lean on your own understanding.' },
      { translation: 'WEB', book: 'Matthew', chapter: 5, verse: 3, text: 'Blessed are the poor in spirit, for theirs is the Kingdom of Heaven.' },
      { translation: 'WEB', book: 'John', chapter: 1, verse: 1, text: 'In the beginning was the Word, and the Word was with God, and the Word was God.' },
      { translation: 'WEB', book: 'John', chapter: 3, verse: 16, text: 'For God so loved the world, that he gave his one and only Son, that whoever believes in him should not perish, but have eternal life.' },
      { translation: 'WEB', book: 'John', chapter: 14, verse: 6, text: 'Jesus said to him, "I am the way, the truth, and the life. No one comes to the Father, except through me."' },
      { translation: 'WEB', book: 'Romans', chapter: 8, verse: 28, text: 'We know that all things work together for good for those who love God, to those who are called according to his purpose.' },
      { translation: 'WEB', book: 'Philippians', chapter: 4, verse: 13, text: 'I can do all things through Christ, who strengthens me.' }
    ];

    const transaction = db.transaction((verses) => {
      for (const v of verses) {
        insertVerse.run(v);
        insertFTS.run(v);
      }
    });

    transaction(seededVerses);
  }

  return db;
}
