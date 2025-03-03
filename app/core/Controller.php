<?php
namespace App\Core;

class Controller 
{
    protected $request;
    protected $response;
    protected $app;

    public function __construct() 
    {
        $this->app = Application::getInstance();
        $this->request = $this->app->getRequest();
        $this->response = $this->app->getResponse();
    }

    /**
     * Render view
     */
    protected function view($view, $data = []) 
    {
        return $this->response->view($view, $data);
    }

    /**
     * Send JSON response
     */
    protected function json($data, $statusCode = 200) 
    {
        return $this->response->json($data, $statusCode);
    }

    /**
     * Redirect to URL
     */
    protected function redirect($url) 
    {
        return $this->response->redirect($url);
    }

    /**
     * Get query parameter
     */
    protected function getQuery($key = null, $default = null) 
    {
        return $this->request->getQuery($key, $default);
    }

    /**
     * Get post data
     */
    protected function getPost($key = null, $default = null) 
    {
        return $this->request->getPost($key, $default);
    }

    /**
     * Get request body
     */
    protected function getBody() 
    {
        return $this->request->getBody();
    }

    /**
     * Get uploaded files
     */
    protected function getFiles($key = null) 
    {
        return $this->request->getFiles($key);
    }

    /**
     * Check if request is POST
     */
    protected function isPost() 
    {
        return $this->request->isPost();
    }

    /**
     * Check if request is GET
     */
    protected function isGet() 
    {
        return $this->request->isGet();
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax() 
    {
        return $this->request->isAjax();
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated() 
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get authenticated user
     */
    protected function getUser() 
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        // Get user from database
        $db = $this->app->getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission($permission) 
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        // Get user permissions from database
        $db = $this->app->getDB();
        $stmt = $db->prepare("
            SELECT p.name 
            FROM permissions p 
            JOIN role_permissions rp ON p.id = rp.permission_id 
            JOIN roles r ON rp.role_id = r.id 
            JOIN users u ON u.role_id = r.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $permissions = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return in_array($permission, $permissions);
    }

    /**
     * Set flash message
     */
    protected function setFlash($type, $message) 
    {
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Get flash message
     */
    protected function getFlash($type) 
    {
        if (!isset($_SESSION['flash'][$type])) {
            return null;
        }

        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }

    /**
     * Validate request data
     */
    protected function validate($rules) 
    {
        return $this->request->validate($rules);
    }

    /**
     * Get config value
     */
    protected function config($key, $default = null) 
    {
        return $this->app->getConfig()->get($key, $default);
    }

    /**
     * Send file download
     */
    protected function download($filePath, $filename = null) 
    {
        return $this->response->download($filePath, $filename);
    }

    /**
     * Send file inline
     */
    protected function file($filePath, $contentType = null) 
    {
        return $this->response->file($filePath, $contentType);
    }

    /**
     * Send error response
     */
    protected function error($message, $code = 500) 
    {
        return $this->response->error($message, $code);
    }

    /**
     * Send unauthorized response
     */
    protected function unauthorized($message = 'Unauthorized') 
    {
        return $this->response->unauthorized($message);
    }

    /**
     * Send forbidden response
     */
    protected function forbidden($message = 'Forbidden') 
    {
        return $this->response->forbidden($message);
    }

    /**
     * Send not found response
     */
    protected function notFound($message = 'Not Found') 
    {
        return $this->response->notFound($message);
    }

    /**
     * Send bad request response
     */
    protected function badRequest($message = 'Bad Request') 
    {
        return $this->response->badRequest($message);
    }
}
