<?php
namespace App\Controllers;

class AuthController
{
    /**
     * Handle user login
     * 
     * @param string $username The username
     * @param string $password The password
     * @return bool Whether login was successful
     */
    public function login($username, $password)
    {
        // For demonstration purposes using hardcoded credentials
        // In a real application, you should check against database values
        if ($username === 'admin' && $password === 'password') {
            // Store user information in session
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'admin';
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();

            return true;
        }

        return false;
    }

    /**
     * Check if user is logged in
     * 
     * @return bool Whether user is logged in
     */
    public function isLoggedIn()
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Check if user has a specific role
     * 
     * @param string $role The role to check for
     * @return bool Whether user has the role
     */
    public function hasRole($role)
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
}