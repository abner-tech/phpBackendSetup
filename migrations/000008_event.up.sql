CREATE TABLE IF NOT EXISTS event (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    event_name CHARACTER VARYING NOT NULL,
    description TEXT,
    visible BOOLEAN NOT NULL DEFAULT TRUE,
    created_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
);