<?php

namespace BruggeMatheus\ServiceLayer\Console;

use Illuminate\Console\GeneratorCommand;

class MakeServiceCommand extends GeneratorCommand
{
    protected $name = 'make:service';

    protected $description = 'Create a new ApplicationService class';

    protected $type = 'Service';

    protected function getStub(): string
    {
        return __DIR__.'/stubs/service.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Services';
    }
}
