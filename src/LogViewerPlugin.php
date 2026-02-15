<?php

namespace Rutale\LogViewer;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Rutale\LogViewer\Filament\Server\Pages\LogViewer;

class LogViewerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'log-viewer';
    }

    public function register(Panel $panel): void
    {
        if ($panel->getId() === 'server') {
            $panel->pages([
                LogViewer::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }
}
