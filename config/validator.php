<?php
// Validation utilities
class Validator {
    public static function required($value, $fieldName) {
        if (empty(trim($value))) {
            throw new InvalidArgumentException("$fieldName este obligatoriu.");
        }
        return trim($value);
    }
    
    public static function minLength($value, $min, $fieldName) {
        if (mb_strlen($value) < $min) {
            throw new InvalidArgumentException("$fieldName trebuie să aibă minim $min caractere.");
        }
        return $value;
    }
    
    public static function maxLength($value, $max, $fieldName) {
        if (mb_strlen($value) > $max) {
            throw new InvalidArgumentException("$fieldName trebuie să aibă maxim $max caractere.");
        }
        return $value;
    }
    
    public static function email($value, $fieldName) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("$fieldName nu este un email valid.");
        }
        return $value;
    }
    
    public static function date($value, $fieldName) {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException("$fieldName nu este o dată validă.");
        }
        return $value;
    }
    
    public static function url($value, $fieldName) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("$fieldName nu este un URL valid.");
        }
        return $value;
    }
    
    public static function slug($value) {
        return preg_replace('/[^a-z0-9\-]/', '', strtolower($value));
    }
}
