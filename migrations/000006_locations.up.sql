CREATE TABLE IF NOT EXISTS location (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    location_name VARCHAR(255) UNIQUE NOT NULL,
    location_address VARCHAR UNIQUE,
    created_timestamp TIMESTAMP(0)
    WITH
        TIME ZONE NOT NULL DEFAULT NOW()
);