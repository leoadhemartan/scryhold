<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // Note: In Laravel 12, middleware is primarily configured in bootstrap/app.php
        // This property is maintained for compatibility but most configuration
        // should be done via the bootstrap/app.php withMiddleware() method
    ];
}
