

-- CREATE TABLE IF NOT EXISTS location (
--     id BIGSERIAL NOT NULL PRIMARY KEY,
--     location_name VARCHAR(255) UNIQUE NOT NULL,
--     location_address VARCHAR UNIQUE,
--     created_timestamp TIMESTAMP(0)
--     WITH
--         TIME ZONE NOT NULL DEFAULT NOW()
-- );

-- update to more verbose address

CREATE TABLE IF NOT EXISTS location  (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    farm_name VARCHAR(255) UNIQUE NOT NULL,
    street_address VARCHAR,
    city VARCHAR,
    district VARCHAR NOT NULL,
    notes TEXT,
    created_timestamp TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
)