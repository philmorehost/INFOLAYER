-- SQLite Schema for Offline Bible & Song Data

CREATE TABLE IF NOT EXISTS translations (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    language TEXT DEFAULT 'en'
);

CREATE TABLE IF NOT EXISTS verses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    translation TEXT NOT NULL,
    book TEXT NOT NULL,
    chapter INTEGER NOT NULL,
    verse INTEGER NOT NULL,
    text TEXT NOT NULL,
    FOREIGN KEY(translation) REFERENCES translations(id)
);

CREATE VIRTUAL TABLE IF NOT EXISTS verses_fts USING fts5(
    translation UNINDEXED,
    book UNINDEXED,
    chapter UNINDEXED,
    verse UNINDEXED,
    text
);

CREATE TABLE IF NOT EXISTS songs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    author TEXT,
    ccli_number TEXT,
    lyrics_json TEXT NOT NULL
);

INSERT OR IGNORE INTO translations (id, name) VALUES ('KJV', 'King James Version');
INSERT OR IGNORE INTO translations (id, name) VALUES ('WEB', 'World English Bible');
