CREATE TABLE IF NOT EXISTS weight_log (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    animal_id BIGINT REFERENCES animal(id) ON DELETE CASCADE,
    weight BIGINT NOT NULL,
    memo CHARACTER VARYING,
    added_by_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    created_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
)