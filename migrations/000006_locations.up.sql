CREATE TABLE IF NOT EXISTS location (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    farm_name CHARACTER VARYING NOT NULL,
    location CHARACTER VARYING
);