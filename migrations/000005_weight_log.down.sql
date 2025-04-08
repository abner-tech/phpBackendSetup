ALTER TABLE IF EXISTS image
    DROP COLUMN IF EXISTS weight_id;

ALTER TABLE IF EXISTS weight_log
    DROP COLUMN animal_id;

DROP TABLE IF EXISTS weight_log;
