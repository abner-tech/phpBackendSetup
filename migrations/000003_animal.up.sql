CREATE TABLE IF NOT EXISTS animal (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    blpa_number INTEGER UNIQUE,
    color_id BIGINT NOT NULL REFERENCES color(id) ON DELETE CASCADE,
    sire_id BIGINT REFERENCES animal(id) ON DELETE CASCADE,
    dam_id BIGINT REFERENCES animal(id) ON DELETE CASCADE,
    dob DATE,
    gender CHARACTER VARYING,
    added_by_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    visible BOOLEAN NOT NULL DEFAULT TRUE,
    created_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
);