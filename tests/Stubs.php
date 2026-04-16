<?php

use BruggeMatheus\ServiceLayer\ApplicationService;
use Illuminate\Support\Facades\DB;

class SumService extends ApplicationService
{
    public function __construct(
        public readonly int $a,
        public readonly int $b,
    ) {}

    protected function rules(): array
    {
        return ['a' => 'required|integer', 'b' => 'required|integer'];
    }

    public function run(): mixed
    {
        return $this->a + $this->b;
    }
}

class EmailService extends ApplicationService
{
    public function __construct(public readonly string $email) {}

    protected function rules(): array
    {
        return ['email' => 'required|email'];
    }

    public function run(): mixed
    {
        return $this->email;
    }
}

class PersonService extends ApplicationService
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $age,
    ) {}

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:3',
            'email' => 'required|email',
            'age' => 'required|integer|min:18',
        ];
    }

    public function run(): mixed
    {
        return ['name' => $this->name, 'email' => $this->email, 'age' => $this->age];
    }
}

class SpyService extends ApplicationService
{
    public bool $runCalled = false;

    public function __construct(public readonly string $name) {}

    protected function rules(): array
    {
        return ['name' => 'required|string|min:1'];
    }

    public function run(): mixed
    {
        $this->runCalled = true;

        return $this->name;
    }
}

class ThrowingService extends ApplicationService
{
    protected function rules(): array
    {
        return [];
    }

    public function run(): mixed
    {
        throw new RuntimeException('Forced failure');
    }
}

class NullService extends ApplicationService
{
    protected function rules(): array
    {
        return [];
    }

    public function run(): mixed
    {
        return null;
    }
}

class PersistService extends ApplicationService
{
    public function __construct(public readonly string $value) {}

    protected function rules(): array
    {
        return ['value' => 'required|string'];
    }

    public function run(): mixed
    {
        DB::table('test_records')->insert(['value' => $this->value]);

        return $this->value;
    }
}

class RollbackService extends ApplicationService
{
    public function __construct(public readonly string $value) {}

    protected function rules(): array
    {
        return ['value' => 'required|string'];
    }

    public function run(): mixed
    {
        DB::table('test_records')->insert(['value' => $this->value]);
        throw new RuntimeException('Forced rollback');
    }
}
