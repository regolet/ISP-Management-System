<?php
namespace App\Core;

class Request
{
    private array $queryParams;
    private array $postData;
    private array $serverData;
    private array $cookies;
    private array $files;

    public function __construct()
    {
        $this->queryParams = $_GET;
        $this->postData = $_POST;
        $this->serverData = $_SERVER;
        $this->cookies = $_COOKIE;
        $this->files = $_FILES;
    }

    public function getQuery($key = null, $default = null)
    {
        if ($key === null) {
            return $this->queryParams;
        }
        return $this->queryParams[$key] ?? $default;
    }

    public function getPost($key = null, $default = null)
    {
        if ($key === null) {
            return $this->postData;
        }
        return $this->postData[$key] ?? $default;
    }

    public function getBody()
    {
        $body = file_get_contents('php://input');
        return json_decode($body, true) ?? [];
    }

    public function getFiles($key = null)
    {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }

    public function getCookie($key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }
        return $this->cookies[$key] ?? $default;
    }

    public function getMethod(): string
    {
        return strtoupper($this->serverData['REQUEST_METHOD']);
    }

    public function getUri(): string
    {
        return $this->serverData['REQUEST_URI'];
    }

    public function getPath(): string
    {
        $path = $this->serverData['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position === false) {
            return $path;
        }
        return substr($path, 0, $position);
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isPut(): bool
    {
        return $this->getMethod() === 'PUT';
    }

    public function isDelete(): bool
    {
        return $this->getMethod() === 'DELETE';
    }

    public function isAjax(): bool
    {
        return isset($this->serverData['HTTP_X_REQUESTED_WITH']) &&
            strtolower($this->serverData['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function getHeader($key)
    {
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->serverData[$headerKey] ?? null;
    }

    public function validate(array $rules): array
    {
        $errors = [];
        $data = array_merge($this->getPost(), $this->getBody());

        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                if (strpos($rule, 'required') !== false) {
                    $errors[$field][] = "The {$field} field is required.";
                }
                continue;
            }

            $value = $data[$field];
            $rulesList = explode('|', $rule);

            foreach ($rulesList as $ruleItem) {
                $ruleParams = explode(':', $ruleItem);
                $ruleName = $ruleParams[0];
                $ruleValue = $ruleParams[1] ?? null;

                switch ($ruleName) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = "The {$field} field is required.";
                        }
                        break;
                    case 'min':
                        if (strlen($value) < $ruleValue) {
                            $errors[$field][] = "The {$field} must be at least {$ruleValue} characters.";
                        }
                        break;
                    case 'max':
                        if (strlen($value) > $ruleValue) {
                            $errors[$field][] = "The {$field} may not be greater than {$ruleValue} characters.";
                        }
                        break;
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "The {$field} must be a valid email address.";
                        }
                        break;
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field][] = "The {$field} must be a number.";
                        }
                        break;
                }
            }
        }

        return $errors;
    }
}
