CREATE TABLE IF NOT EXISTS event_log (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    event_id INT NOT NULL REFERENCES event(id),
    animal_id INT NOT NULL REFERENCES animal(id),
    status VARCHAR,
    memo TEXT,
    weight_id INT REFERENCES weight_log(id),
    visible BOOLEAN NOT NULL DEFAULT TRUE,
    event_date timestamp(0) WITH TIME ZONE NOT NULL,
    created_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
);