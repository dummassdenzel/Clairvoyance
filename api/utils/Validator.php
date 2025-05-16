<?php

class Validator
{
    /**
     * Validate that required fields are present in the data array
     * 
     * @param array $data The data array to validate
     * @param array $requiredFields Array of required field names
     * @return array [isValid (bool), message (string)]
     */
    public static function validateRequired($data, $requiredFields)
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return [
                    'isValid' => false,
                    'message' => "Required field '$field' is missing or empty"
                ];
            }
        }
        
        return ['isValid' => true, 'message' => 'Validation passed'];
    }
    
    /**
     * Sanitize input data to prevent XSS and SQL injection
     * 
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    public static function sanitize($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
        } else {
            $data = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
    
    /**
     * Validate email format
     * 
     * @param string $email Email to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate numeric value
     * 
     * @param mixed $value Value to validate
     * @return bool True if numeric, false otherwise
     */
    public static function isNumeric($value)
    {
        return is_numeric($value);
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date string to validate
     * @param string $format Expected date format
     * @return bool True if valid, false otherwise
     */
    public static function isValidDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
} 