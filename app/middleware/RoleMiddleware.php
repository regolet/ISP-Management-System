<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Application;

class RoleMiddleware extends Middleware
{
    /**
     * Handle middleware
     * @param array $args Middleware arguments
     * @return bool Continue execution
     */
    public function handle(array $args = []): bool
    {
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return false;
        }

        // If no roles specified, just check authentication
        if (empty($args)) {
            return true;
        }

        // Check if user has any of the specified roles
        $role = $args[0] ?? null;
        if ($role && !$this->hasRole($role)) {
            if ($this->isAjax()) {
                $this->forbidden('You do not have permission to access this resource');
            }
            $this->redirect('/login');
            return false;
        }

        return true;
    }
}
