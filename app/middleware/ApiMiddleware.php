<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Application;

class ApiMiddleware extends Middleware 
{
    protected $app;
    protected $rateLimit;
    protected $rateLimitWindow;

    public function __construct() 
    {
        $this->app = Application::getInstance();
        $this->rateLimit = $this->app->getConfig('api.throttle.max_attempts', 60);
        $this->rateLimitWindow = $this->app->getConfig('api.throttle.decay_minutes', 1) * 60;
    }

    /**
     * Handle API request
     */
    public function handle($args = []) 
    {
        // Set JSON response headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: ' . implode(',', $this->app->getConfig('security.cors.allowed_origins', ['*'])));
        header('Access-Control-Allow-Methods: ' . implode(',', $this->app->getConfig('security.cors.allowed_methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])));
        header('Access-Control-Allow-Headers: ' . implode(',', $this->app->getConfig('security.cors.allowed_headers', ['Content-Type', 'Authorization'])));
        header('Access-Control-Max-Age: ' . $this->app->getConfig('security.cors.max_age', 0));

        // Handle preflight request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // Check rate limiting
        if ($this->app->getConfig('api.throttle.enabled', true)) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $key = "rate_limit:{$ip}";

            // Get current requests count
            $requests = apcu_fetch($key) ?: 0;

            // Check if limit exceeded
            if ($requests >= $this->rateLimit) {
                $this->app->getResponse()->json([
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded'
                ], 429);
                return false;
            }

            // Increment requests count
            if ($requests === 0) {
                apcu_add($key, 1, $this->rateLimitWindow);
            } else {
                apcu_inc($key);
            }

            // Set rate limit headers
            header('X-RateLimit-Limit: ' . $this->rateLimit);
            header('X-RateLimit-Remaining: ' . ($this->rateLimit - $requests - 1));
        }

        // Check API authentication
        $token = $this->getBearerToken();
        
        if (!$token) {
            $this->app->getResponse()->json([
                'error' => 'Unauthorized',
                'message' => 'No API token provided'
            ], 401);
            return false;
        }

        // Verify token
        try {
            $payload = $this->verifyToken($token);
            
            // Store user data in request
            $_REQUEST['api_user'] = $payload;
            
            return true;

        } catch (\Exception $e) {
            $this->app->getResponse()->json([
                'error' => 'Unauthorized',
                'message' => $e->getMessage()
            ], 401);
            return false;
        }
    }

    /**
     * Get bearer token from request
     */
    protected function getBearerToken() 
    {
        $headers = $this->app->getRequest()->getHeaders();
        
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }

    /**
     * Verify JWT token
     */
    protected function verifyToken($token) 
    {
        $key = $this->app->getConfig('security.jwt_secret');
        $algorithm = $this->app->getConfig('security.jwt_algorithm', 'HS256');

        try {
            // Split token
            [$header, $payload, $signature] = explode('.', $token);

            // Verify signature
            $valid = hash_hmac(
                'sha256',
                $header . '.' . $payload,
                $key,
                true
            );

            if (base64_encode($valid) !== str_replace(['-', '_'], ['+', '/'], $signature)) {
                throw new \Exception('Invalid token signature');
            }

            // Decode payload
            $payload = json_decode(base64_decode($payload), true);

            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new \Exception('Token has expired');
            }

            return $payload;

        } catch (\Exception $e) {
            throw new \Exception('Invalid token format');
        }
    }

    /**
     * Generate JWT token
     */
    public static function generateToken($payload) 
    {
        $app = Application::getInstance();
        $key = $app->getConfig('security.jwt_secret');
        $algorithm = $app->getConfig('security.jwt_algorithm', 'HS256');
        $lifetime = $app->getConfig('api.token_lifetime', 86400);

        // Add expiration time
        $payload['exp'] = time() + $lifetime;

        // Create token parts
        $header = base64_encode(json_encode([
            'typ' => 'JWT',
            'alg' => $algorithm
        ]));

        $payload = base64_encode(json_encode($payload));

        // Create signature
        $signature = base64_encode(
            hash_hmac('sha256', $header . '.' . $payload, $key, true)
        );

        // Create token
        return $header . '.' . $payload . '.' . $signature;
    }
}
