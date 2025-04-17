CREATE TABLE IF NOT EXISTS event (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    event_name CHARACTER VARYING NOT NULL,
    description TEXT,
    created_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
);