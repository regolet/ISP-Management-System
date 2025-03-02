<?php
namespace App\Middleware;

use App\Core\Request;

abstract class Middleware 
{
    protected $next;

    public function setNext(Middleware $middleware): Middleware 
    {
        $this->next = $middleware;
        return $middleware;
    }

    public function handle(Request $request) 
    {
        if ($this->next) {
            return $this->next->handle($request);
        }
        return true;
    }
}
