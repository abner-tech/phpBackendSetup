/* to allow type cytext on our database */
CREATE EXTENSION IF NOT EXISTS citext;

CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    admin boolean DEFAULT false,
    firstname TEXT NOT NULL,
    lastname TEXT NOT NULL,
    email citext UNIQUE NOT NULL,
    phone varchar(12),
    password_hash bytea NOT NULL,
    created_at timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
);