<?php
// Server-side form validation (back-end).
// Browser checks (HTML + js/validation.js) can be skipped by anyone, so the server
// has to check everything again. Each function returns an error message, or null if the value is fine.
class Validator {

    // value must not be empty (spaces only counts as empty)
    static function required($value, $label) {
        if (trim((string)$value) === '') return "$label is required";
        return null;
    }

    // value must not be longer than $max characters
    static function maxLength($value, $max, $label) {
        if (mb_strlen(trim((string)$value)) > $max) return "$label must be at most $max characters";
        return null;
    }

    // date must look like 2026-11-30 AND be a real calendar date (no 2026-02-31)
    static function date($value, $label = 'Date') {
        $d = DateTime::createFromFormat('Y-m-d', (string)$value);
        if (!$d || $d->format('Y-m-d') !== $value) return "$label is not a valid date";
        return null;
    }

    // time like 14:30 (empty is allowed because time is optional)
    static function time($value, $label = 'Time') {
        if ($value === '' || $value === null) return null;
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) return "$label is not a valid time";
        return null;
    }

    // value must be one of the allowed choices (stops edited dropdown values)
    static function inList($value, $allowed, $label) {
        if (!in_array($value, $allowed, true)) return "$label has an invalid choice";
        return null;
    }

    // runs a list of checks, gives back the first error found (or null if all pass)
    static function firstError($results) {
        foreach ($results as $r) {
            if ($r !== null) return $r;
        }
        return null;
    }
}
