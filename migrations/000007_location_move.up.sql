CREATE TABLE IF NOT EXISTS location_move (
    id BIGSERIAL NOT NULL PRIMARY KEY,
    animal_id INT NOT NULL REFERENCES animal(id) ON DELETE CASCADE,
    new_location_name VARCHAR(255) NOT NULL REFERENCES location(location_name) ON DELETE CASCADE,
    old_location_name VARCHAR(255) REFERENCES location(location_name) ON DELETE CASCADE,
    added_by_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    weight_id INT REFERENCES weight_log(id) ON DELETE CASCADE,
    image_id INT REFERENCES image(id) ON DELETE CASCADE,
    created_timestamp timestamp(0) WITH TIME ZONE NOT NULL DEFAULT NOW()
);