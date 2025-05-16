<?php

class Response
{
    /**
     * Send a JSON response with the given status, message, data, and HTTP code
     * 
     * @param string $status Success or error status
     * @param string $message Human-readable message
     * @param mixed $data Data payload to return (optional)
     * @param int $httpCode HTTP status code
     * @return string JSON encoded response
     */
    public static function send($status, $message, $data = null, $httpCode = 200)
    {
        http_response_code($httpCode);
        
        $response = [
            'status' => $status,
            'message' => $message,
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Send a success response
     * 
     * @param string $message Success message
     * @param mixed $data Data payload
     * @param int $httpCode HTTP status code (default 200)
     */
    public static function success($message, $data = null, $httpCode = 200)
    {
        self::send('success', $message, $data, $httpCode);
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param mixed $data Additional error data (optional)
     * @param int $httpCode HTTP status code (default 400)
     */
    public static function error($message, $data = null, $httpCode = 400)
    {
        self::send('error', $message, $data, $httpCode);
    }
    
    /**
     * Send a not found response
     * 
     * @param string $message Not found message
     */
    public static function notFound($message = 'Resource not found')
    {
        self::error($message, null, 404);
    }
    
    /**
     * Send an unauthorized response
     * 
     * @param string $message Unauthorized message
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        self::error($message, null, 401);
    }
} 