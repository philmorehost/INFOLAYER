import Database from 'better-sqlite3';
import * as fs from 'fs';
import * as path from 'path';

export function initializeBibleDatabase(dbPath: string): Database.Database {
  const db = new Database(dbPath);

  const schema = fs.readFileSync(path.join(__dirname, 'schema.sql'), 'utf-8');
  db.exec(schema);

  const checkVerses = db.prepare('SELECT COUNT(*) as count FROM verses').get() as { count: number };

  if (checkVerses.count === 0) {
    const insertVerse = db.prepare(`
      INSERT INTO verses (translation, book, chapter, verse, text)
      VALUES (@translation, @book, @chapter, @verse, @text)
    `);

    const insertFTS = db.prepare(`
      INSERT INTO verses_fts (translation, book, chapter, verse, text)
      VALUES (@translation, @book, @chapter, @verse, @text)
    `);

    const seededVerses = [
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

  // Seed Worship Hymns
  const checkSongs = db.prepare('SELECT COUNT(*) as count FROM songs').get() as { count: number };
  if (checkSongs.count === 0) {
    const insertSong = db.prepare(`
      INSERT INTO songs (title, author, ccli_number, lyrics_json)
      VALUES (@title, @author, @ccli_number, @lyrics_json)
    `);

    const hymns = [
      {
        title: 'Amazing Grace',
        author: 'John Newton',
        ccli_number: '22081',
        lyrics_json: JSON.stringify([
          'Amazing grace! How sweet the sound, That saved a wretch like me!',
          'I once was lost, but now am found; Was blind, but now I see.',
          '’Twas grace that taught my heart to fear, And grace my fears relieved;',
          'How precious did that grace appear The hour I first believed!'
        ])
      },
      {
        title: 'How Great Thou Art',
        author: 'Carl Boberg',
        ccli_number: '14181',
        lyrics_json: JSON.stringify([
          'O Lord my God, when I in awesome wonder, Consider all the worlds Thy Hands have made;',
          'I see the stars, I hear the rolling thunder, Thy power throughout the universe displayed.',
          'Then sings my soul, My Saviour God, to Thee, How great Thou art, how great Thou art!'
        ])
      }
    ];

    const songTx = db.transaction((songs) => {
      for (const s of songs) insertSong.run(s);
    });

    songTx(hymns);
  }

  return db;
}
