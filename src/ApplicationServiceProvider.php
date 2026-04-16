<?php

namespace BruggeMatheus\ServiceLayer;

use BruggeMatheus\ServiceLayer\Console\MakeServiceCommand;
use Illuminate\Support\ServiceProvider;

class ApplicationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeServiceCommand::class,
            ]);
        }
    }
}
