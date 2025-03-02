<?php
namespace App\Core;

class Request 
{
    /**
     * Get request path
     */
    public function getPath() 
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position === false) {
            return $path;
        }
        return substr($path, 0, $position);
    }

    /**
     * Get request method
     */
    public function getMethod() 
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get query parameter
     */
    public function getQuery($key, $default = null) 
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get post parameter
     */
    public function getPost($key, $default = null) 
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get request body
     */
    public function getBody() 
    {
        $body = [];
        
        if ($this->getMethod() === 'GET') {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        
        if ($this->getMethod() === 'POST') {
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
        
        return $body;
    }

    /**
     * Get JSON request body
     */
    public function getJson() 
    {
        if ($this->getMethod() === 'POST' || $this->getMethod() === 'PUT') {
            $json = file_get_contents('php://input');
            return json_decode($json, true);
        }
        return null;
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax() 
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get request headers
     */
    public function getHeaders() 
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get client IP address
     */
    public function getIp() 
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * Get user agent
     */
    public function getUserAgent() 
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
