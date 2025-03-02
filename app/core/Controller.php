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
        return auth();
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission($permission) 
    {
        return hasPermission($permission);
    }

    /**
     * Set flash message
     */
    protected function setFlash($type, $message) 
    {
        setFlash($type, $message);
    }

    /**
     * Get flash message
     */
    protected function getFlash($type) 
    {
        return getFlash($type);
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
        return $this->app->getConfig($key, $default);
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

    /**
     * Get database connection
     */
    protected function db() 
    {
        return $this->app->getDB();
    }

    /**
     * Begin database transaction
     */
    protected function beginTransaction() 
    {
        $this->db()->begin_transaction();
    }

    /**
     * Commit database transaction
     */
    protected function commit() 
    {
        $this->db()->commit();
    }

    /**
     * Rollback database transaction
     */
    protected function rollback() 
    {
        $this->db()->rollback();
    }
}
