<?php
namespace App\Core;

abstract class Middleware 
{
    /**
     * Handle middleware
     * @param array $args Middleware arguments
     * @return bool Continue execution
     */
    abstract public function handle($args = []);
}
