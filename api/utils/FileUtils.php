<?php

/**
 * Utility functions for file operations
 */
class FileUtils
{
    /**
     * Sanitize a filename to make it safe for file system operations
     * 
     * @param string $filename Filename to sanitize
     * @return string Sanitized filename
     */
    public static function sanitizeFilename($filename)
    {
        // Remove any character that is not alphanumeric, underscore, dash, or dot
        $filename = preg_replace('/[^\w\-\.]/', '_', $filename);
        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);
        return $filename;
    }
}
