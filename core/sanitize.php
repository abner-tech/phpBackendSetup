<?php

use PgSql\Result;

class Sanitize
{
    public function __construct()
    {

    }
    public function sanitizeIntegerOrNull($value)
    {
        $sanitized = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        return ($sanitized === false || $sanitized === null) ? null : $sanitized;
    }

    // Function to sanitize a string and return null if empty
    public function sanitizeStringOrNull($value)
    {
        $sanitized = filter_var($value, FILTER_SANITIZE_STRING);
        return empty($sanitized) ? null : $sanitized;
    }

    // Function to sanitize a date and return null if invalid
    public function sanitizeDateOrNull($date)
    {
        if (empty($date)) {
            return null;
        }
        $formattedDate = DateTime::createFromFormat('Y-m-d', $date);
        return ($formattedDate && $formattedDate->format('Y-m-d') === $date) ? $formattedDate->format('Y-m-d') : null;
    }

}

?>