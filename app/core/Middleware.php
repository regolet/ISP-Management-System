<?php
namespace App\Core;

abstract class Middleware 
{
    /**
     * Handle middleware
     * @param array $args Middleware arguments
     * @return bool Continue execution
     */
    abstract public function handle(array $args = []): bool;

    /**
     * Get middleware arguments from string
     * Example: "RoleMiddleware:admin,editor" -> ['admin', 'editor']
     */
    protected function getArgs(string $middleware): array
    {
        $parts = explode(':', $middleware);
        if (count($parts) === 1) {
            return [];
        }

        return explode(',', $parts[1]);
    }

    /**
     * Get middleware name from string
     * Example: "RoleMiddleware:admin,editor" -> "RoleMiddleware"
     */
    protected function getName(string $middleware): string
    {
        $parts = explode(':', $middleware);
        return $parts[0];
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get authenticated user's role
     */
    protected function getUserRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Get authenticated user's ID
     */
    protected function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if user has a specific role
     */
    protected function hasRole(string $role): bool
    {
        return $this->getUserRole() === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    protected function hasAnyRole(array $roles): bool
    {
        return in_array($this->getUserRole(), $roles);
    }

    /**
     * Check if user has all of the given roles
     */
    protected function hasAllRoles(array $roles): bool
    {
        $userRole = $this->getUserRole();
        foreach ($roles as $role) {
            if ($userRole !== $role) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax(): bool
    {
        return Application::getInstance()->getRequest()->isAjax();
    }

    /**
     * Send JSON response
     */
    protected function json($data, int $statusCode = 200): never
    {
        Application::getInstance()->getResponse()->json($data, $statusCode);
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url): never
    {
        Application::getInstance()->getResponse()->redirect($url);
    }

    /**
     * Send unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): never
    {
        Application::getInstance()->getResponse()->unauthorized($message);
    }

    /**
     * Send forbidden response
     */
    protected function forbidden(string $message = 'Forbidden'): never
    {
        Application::getInstance()->getResponse()->forbidden($message);
    }

    /**
     * Get config value
     */
    protected function config(string $key, $default = null)
    {
        return Application::getInstance()->getConfig()->get($key, $default);
    }

    /**
     * Get request instance
     */
    protected function request(): Request
    {
        return Application::getInstance()->getRequest();
    }

    /**
     * Get response instance
     */
    protected function response(): Response
    {
        return Application::getInstance()->getResponse();
    }
}
