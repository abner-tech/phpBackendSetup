CREATE TABLE IF NOT EXISTS location_move (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    animal_id INT NOT NULL NOT NULL REFERENCES animal(id),
    new_farm_id INT NOT NULL REFERENCES location(id),
    old_location_move_id INT REFERENCES location_move(id),
    added_by_id INT NOT NULL REFERENCES users(id),
    created_at timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
);