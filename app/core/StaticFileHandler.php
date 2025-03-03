<?php
namespace App\Core;

use App\Core\Exceptions\SecurityException;
use App\Core\Exceptions\FileNotFoundException;

class StaticFileHandler 
{
    private static array $mimeTypes = [
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

    private static array $allowedDirs = [
        'css',
        'js',
        'img',
        'fonts',
        'assets'
    ];

    private static int $maxFileSize = 10485760; // 10MB
    private static array $blockedExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'inc'];

    /**
     * Handle static file request with security checks
     */
    public static function handle(string $path, Config $config): bool 
    {
        try {
            // Security checks
            self::validatePath($path);
            
            // Get file information
            $fileInfo = self::getFileInfo($path);
            
            // Additional security checks
            self::validateFileAccess($fileInfo, $config);
            
            // Serve the file
            self::serveFile($fileInfo);
            
            return true;

        } catch (SecurityException $e) {
            // Log security violations
            error_log("Security violation attempt: " . $e->getMessage());
            http_response_code(403);
            return false;

        } catch (FileNotFoundException $e) {
            return false;

        } catch (\Exception $e) {
            error_log("Static file error: " . $e->getMessage());
            http_response_code(500);
            return false;
        }
    }

    /**
     * Validate file path for security
     */
    private static function validatePath(string $path): void 
    {
        // Remove query string and decode URL
        $path = urldecode(parse_url($path, PHP_URL_PATH));
        
        // Basic path sanitization
        $path = str_replace(['\\', '../', './'], '/', $path);
        $path = preg_replace('/\/+/', '/', $path);
        
        // Check for directory traversal attempts
        if (strpos($path, '../') !== false || strpos($path, '..\\') !== false) {
            throw new SecurityException('Directory traversal attempt detected');
        }
        
        // Get directory from path
        $dir = explode('/', trim($path, '/'))[0];
        
        // Check if directory is allowed
        if (!in_array($dir, self::$allowedDirs)) {
            throw new SecurityException('Access to directory not allowed');
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, self::$blockedExtensions)) {
            throw new SecurityException('File type not allowed');
        }
    }

    /**
     * Get file information with security checks
     */
    private static function getFileInfo(string $path): array 
    {
        // Construct safe file path
        $publicPath = realpath(PathManager::getInstance()->get('public'));
        if ($publicPath === false) {
            throw new SecurityException('Invalid public directory');
        }

        $filePath = realpath($publicPath . '/' . ltrim($path, '/'));
        if ($filePath === false) {
            throw new FileNotFoundException('File not found');
        }

        // Verify file is within public directory
        if (strpos($filePath, $publicPath) !== 0) {
            throw new SecurityException('Access outside public directory');
        }

        // Get file extension and mime type
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!isset(self::$mimeTypes[$extension])) {
            throw new SecurityException('Invalid file type');
        }

        return [
            'path' => $filePath,
            'extension' => $extension,
            'mime_type' => self::$mimeTypes[$extension]
        ];
    }

    /**
     * Validate file access with additional security checks
     */
    private static function validateFileAccess(array $fileInfo, Config $config): void 
    {
        // Check if file exists and is actually a file
        if (!file_exists($fileInfo['path']) || !is_file($fileInfo['path'])) {
            throw new FileNotFoundException('File not found');
        }

        // Check file permissions
        if (!is_readable($fileInfo['path'])) {
            throw new SecurityException('File not readable');
        }

        // Check file size
        if (filesize($fileInfo['path']) > self::$maxFileSize) {
            throw new SecurityException('File exceeds maximum size limit');
        }

        // Verify file type by checking actual content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileInfo['path']);
        finfo_close($finfo);

        if ($mimeType !== $fileInfo['mime_type']) {
            throw new SecurityException('File type mismatch');
        }

        // Additional security checks in production
        if ($config->isProduction()) {
            // Check file ownership
            $fileOwner = fileowner($fileInfo['path']);
            $processOwner = posix_getuid();
            if ($fileOwner === false || $fileOwner === $processOwner) {
                throw new SecurityException('Invalid file ownership');
            }

            // Check file permissions (only allow 644 or 444 in production)
            $perms = fileperms($fileInfo['path']) & 0777;
            if ($perms !== 0644 && $perms !== 0444) {
                throw new SecurityException('Invalid file permissions');
            }
        }
    }

    /**
     * Serve the file with proper headers
     */
    private static function serveFile(array $fileInfo): void 
    {
        $response = new Response();

        // Set content type and security headers
        $response->setContentType($fileInfo['mime_type']);
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('Content-Security-Policy', "default-src 'self'");
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->setHeader('X-XSS-Protection', '1; mode=block');

        // Set cache headers
        $response->setHeader('Cache-Control', 'public, max-age=31536000');
        $response->setHeader('Pragma', 'cache');
        $response->setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));

        // Generate and check ETag
        $etag = '"' . hash_file('sha256', $fileInfo['path']) . '"';
        $response->setHeader('ETag', $etag);

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            $response->setStatusCode(304);
            return;
        }

        // Send file
        $response->file($fileInfo['path']);
    }

    /**
     * Add allowed directory
     */
    public static function addAllowedDir(string $dir): void 
    {
        $dir = trim(str_replace(['..', '\\', '/'], '', $dir));
        if (!empty($dir) && !in_array($dir, self::$allowedDirs)) {
            self::$allowedDirs[] = $dir;
        }
    }

    /**
     * Set maximum file size
     */
    public static function setMaxFileSize(int $size): void 
    {
        self::$maxFileSize = $size;
    }

    /**
     * Add mime type
     */
    public static function addMimeType(string $extension, string $mimeType): void 
    {
        self::$mimeTypes[strtolower($extension)] = $mimeType;
    }
}
