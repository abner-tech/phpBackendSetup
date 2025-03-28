CREATE TABLE IF NOT EXISTS image (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    animal_id BIGINT REFERENCES animal(id),
    weight_id BIGINT REFERENCES weight_log(id),
    location_move_id BIGINT,
    image_data BYTEA NOT NULL,
    created_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
)