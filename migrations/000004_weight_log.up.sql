CREATE TABLE IF NOT EXISTS weight_log (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    animal_id BIGINT REFERENCES animal(id),
    weight BIGINT NOT NULL,
    memo CHARACTER VARYING,
    created_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
)