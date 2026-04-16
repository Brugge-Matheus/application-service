<?php

use BruggeMatheus\ServiceLayer\Tests\DatabaseTestCase;
use BruggeMatheus\ServiceLayer\Tests\TestCase;

require_once __DIR__.'/Stubs.php';

uses(TestCase::class)->in('ExecutionTest.php', 'ValidationTest.php', 'Console');
uses(DatabaseTestCase::class)->in('Database');
