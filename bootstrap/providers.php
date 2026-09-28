<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
];
// In bootstrap/app.php or a ServiceProvider:
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL'])) {
    app()->useStoragePath('/tmp/storage');
}