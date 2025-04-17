CREATE TABLE IF NOT EXISTS event_log (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    event_id NOT NULL
);

-- working in event_log.sql