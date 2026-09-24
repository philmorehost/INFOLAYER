-- SQLite Schema for Offline Bible Data with FTS5 Full-Text Search

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

INSERT OR IGNORE INTO translations (id, name) VALUES ('KJV', 'King James Version');
INSERT OR IGNORE INTO translations (id, name) VALUES ('WEB', 'World English Bible');
