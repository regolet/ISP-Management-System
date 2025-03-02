<?php
namespace App\Core;

class StaticFileHandler 
{
    private static $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'pdf' => 'application/pdf',
        'json' => 'application/json'
    ];

    /**
     * Handle static file request
     */
    public static function handle($path) 
    {
        // Remove query string
        $path = parse_url($path, PHP_URL_PATH);
        
        // Get file extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        // Check if file exists in public directory
        $filePath = __DIR__ . '/../../public' . $path;
        
        if (file_exists($filePath) && is_file($filePath)) {
            $response = new Response();
            
            // Set content type
            if (isset(self::$mimeTypes[$extension])) {
                $response->setContentType(self::$mimeTypes[$extension]);
            }
            
            // Set cache headers
            $response->setCache();
            
            // Check if file is cached
            $etag = '"' . md5_file($filePath) . '"';
            $response->setHeader('ETag', $etag);
            
            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
                $response->setStatusCode(304);
                return true;
            }
            
            // Send file
            $response->file($filePath);
            return true;
        }
        
        return false;
    }
}
