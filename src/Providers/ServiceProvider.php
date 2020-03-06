<?php

namespace ThisIsDevelopment\LaravelTestSnapshot;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->commands([
            TestSnapshotCommand::class
        ]);
    }
}
