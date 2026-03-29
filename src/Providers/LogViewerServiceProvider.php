<?php

namespace Rutale\LogViewer\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class LogViewerServiceProvider extends RouteServiceProvider
{
    public function boot(): void
    {
        $this->routes(function () {
            Route::get('/plugins/log-viewer/log-viewer.js', function () {
                return response()->file(
                    plugin_path('log-viewer', 'resources/js/log-viewer.js'),
                    ['Content-Type' => 'application/javascript; charset=utf-8']
                );
            })->name('log-viewer.assets.js');
        });
    }
}
