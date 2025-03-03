<?php
namespace App\Core;

class Response
{
    private array $headers = [];
    private int $statusCode = 200;

    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
        http_response_code($code);
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    private function sendHeaders(): void
    {
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
    }

    public function redirect(string $url): never
    {
        $this->setHeader('Location', $url);
        $this->sendHeaders();
        exit;
    }

    public function json($data, int $statusCode = 200): never
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        $this->sendHeaders();
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    public function view(string $view, array $data = []): string
    {
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->sendHeaders();
        
        extract($data);
        
        $viewPath = dirname(__DIR__) . "/views/{$view}.php";
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View {$view} not found");
        }

        ob_start();
        include $viewPath;
        return ob_get_clean();
    }

    public function download(string $filePath, ?string $filename = null): never
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found");
        }

        $filename = $filename ?? basename($filePath);

        $this->setHeader('Content-Type', 'application/octet-stream');
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->setHeader('Content-Length', (string) filesize($filePath));
        $this->setHeader('Cache-Control', 'no-cache, must-revalidate');
        $this->setHeader('Pragma', 'public');
        $this->sendHeaders();
        
        readfile($filePath);
        exit;
    }

    public function file(string $filePath, ?string $contentType = null): never
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found");
        }

        if ($contentType === null) {
            $contentType = mime_content_type($filePath) ?: 'application/octet-stream';
        }

        $this->setHeader('Content-Type', $contentType);
        $this->setHeader('Content-Length', (string) filesize($filePath));
        $this->setHeader('Cache-Control', 'no-cache, must-revalidate');
        $this->setHeader('Pragma', 'public');
        $this->sendHeaders();
        
        readfile($filePath);
        exit;
    }

    public function error(string $message, int $code = 500): never
    {
        $this->setStatusCode($code);
        $this->json(['error' => $message], $code);
    }

    public function unauthorized(string $message = 'Unauthorized'): never
    {
        $this->error($message, 401);
    }

    public function forbidden(string $message = 'Forbidden'): never
    {
        $this->error($message, 403);
    }

    public function notFound(string $message = 'Not Found'): never
    {
        $this->error($message, 404);
    }

    public function badRequest(string $message = 'Bad Request'): never
    {
        $this->error($message, 400);
    }

    public function success($data = null, string $message = 'Success'): never
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function created($data = null, string $message = 'Created'): never
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 201);
    }

    public function noContent(): never
    {
        $this->setStatusCode(204);
        $this->sendHeaders();
        exit;
    }

    public function html(string $html): never
    {
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->sendHeaders();
        echo $html;
        exit;
    }

    public function text(string $text): never
    {
        $this->setHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->sendHeaders();
        echo $text;
        exit;
    }

    public function xml(string $xml): never
    {
        $this->setHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->sendHeaders();
        echo $xml;
        exit;
    }

    public function csv(array $data, string $filename = 'export.csv'): never
    {
        $this->setHeader('Content-Type', 'text/csv');
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->sendHeaders();
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers if first row is associative
        if (!empty($data) && is_array($data[0])) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
