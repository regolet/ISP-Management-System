<?php
namespace App\Core;

class Response 
{
    /**
     * Set response status code
     */
    public function setStatusCode($code) 
    {
        http_response_code($code);
    }

    /**
     * Redirect to URL
     */
    public function redirect($url) 
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Send JSON response
     */
    public function json($data, $statusCode = 200) 
    {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Render view
     */
    public function view($view, $data = []) 
    {
        // Extract data to make variables available in view
        extract($data);

        // Start output buffering
        ob_start();

        // Include view file
        $viewPath = APP_ROOT . '/views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            throw new \Exception("View {$view} not found");
        }

        // Include view
        require $viewPath;
        $content = ob_get_clean();

        // Check if layout is specified
        if (isset($layout)) {
            $layoutPath = APP_ROOT . '/views/layouts/' . $layout . '.php';
            if (!file_exists($layoutPath)) {
                throw new \Exception("Layout {$layout} not found");
            }
            require $layoutPath;
        } else {
            echo $content;
        }
    }

    /**
     * Send file download
     */
    public function download($filePath, $fileName = null) 
    {
        if (!file_exists($filePath)) {
            $this->setStatusCode(404);
            return;
        }

        $fileName = $fileName ?? basename($filePath);
        
        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($filePath);
        exit;
    }

    /**
     * Send file inline
     */
    public function file($filePath, $contentType = null) 
    {
        if (!file_exists($filePath)) {
            $this->setStatusCode(404);
            return;
        }

        $contentType = $contentType ?? mime_content_type($filePath);
        
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=31536000');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        
        readfile($filePath);
        exit;
    }

    /**
     * Set response header
     */
    public function setHeader($name, $value) 
    {
        header($name . ': ' . $value);
    }

    /**
     * Set response content type
     */
    public function setContentType($type) 
    {
        $this->setHeader('Content-Type', $type);
    }

    /**
     * Set response cache control
     */
    public function setCache($maxAge = 31536000) 
    {
        $this->setHeader('Cache-Control', 'public, max-age=' . $maxAge);
        $this->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    }

    /**
     * Set response no cache
     */
    public function setNoCache() 
    {
        $this->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->setHeader('Pragma', 'no-cache');
    }

    /**
     * Send error response
     */
    public function error($message, $code = 500) 
    {
        $this->setStatusCode($code);
        return $this->view('errors/error', [
            'code' => $code,
            'message' => $message
        ]);
    }

    /**
     * Send unauthorized response
     */
    public function unauthorized($message = 'Unauthorized') 
    {
        return $this->error($message, 401);
    }

    /**
     * Send forbidden response
     */
    public function forbidden($message = 'Forbidden') 
    {
        return $this->error($message, 403);
    }

    /**
     * Send not found response
     */
    public function notFound($message = 'Not Found') 
    {
        return $this->error($message, 404);
    }

    /**
     * Send bad request response
     */
    public function badRequest($message = 'Bad Request') 
    {
        return $this->error($message, 400);
    }
}
