CREATE TABLE IF NOT EXISTS image (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    image_data BYTEA NOT NULL,
    created_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
)