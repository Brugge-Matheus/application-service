<?php

namespace BruggeMatheus\ServiceLayer\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseTestCase extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        Schema::create('test_records', function (Blueprint $table) {
            $table->id();
            $table->text('value');
        });
    }
}
