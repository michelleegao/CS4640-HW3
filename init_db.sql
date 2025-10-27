DROP TABLE IF EXISTS hw3_games CASCADE;
DROP TABLE IF EXISTS hw3_words CASCADE;
DROP TABLE IF EXISTS hw3_users CASCADE;

CREATE TABLE hw3_users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE hw3_words ( -- stores 7-letter target words that have already been played
    word CHAR(7) PRIMARY KEY
);

CREATE TYPE hw3_game_status AS ENUM ('in_progress', 'won', 'lost'); -- game status enum

CREATE TABLE hw3_games ( -- stores the game the user is playing
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES hw3_users(id) ON DELETE CASCADE,
    target_word CHAR(7) NOT NULL REFERENCES hw3_words(word) ON DELETE CASCADE,
    score INTEGER NOT NULL DEFAULT 0,
    status hw3_game_status NOT NULL DEFAULT 'in_progress'
    CONSTRAINT user_word_once UNIQUE (user_id, word)
);