<?php

use BruggeMatheus\ServiceLayer\ApplicationService;
use Illuminate\Support\Facades\DB;

describe('save()', function () {
    it('persists data when run() succeeds', function () {
        (new PersistService(value: 'hello'))->save();
        expect(DB::table('test_records')->count())->toBe(1);
        expect(DB::table('test_records')->value('value'))->toBe('hello');
    });

    it('rolls back when run() throws', function () {
        expect(fn () => (new RollbackService(value: 'hello'))->save())
            ->toThrow(RuntimeException::class);
        expect(DB::table('test_records')->count())->toBe(0);
    });

    it('rolls back all inserts when run() throws mid-transaction', function () {
        $service = new class extends ApplicationService
        {
            protected function rules(): array
            {
                return [];
            }

            public function run(): mixed
            {
                DB::table('test_records')->insert(['value' => 'first']);
                DB::table('test_records')->insert(['value' => 'second']);
                throw new RuntimeException('mid-way failure');
            }
        };

        expect(fn () => $service->save())->toThrow(RuntimeException::class);
        expect(DB::table('test_records')->count())->toBe(0);
    });

    it('persists all inserts when run() has multiple operations', function () {
        $service = new class extends ApplicationService
        {
            protected function rules(): array
            {
                return [];
            }

            public function run(): mixed
            {
                DB::table('test_records')->insert(['value' => 'first']);
                DB::table('test_records')->insert(['value' => 'second']);

                return 2;
            }
        };

        expect($service->save())->toBe(2);
        expect(DB::table('test_records')->count())->toBe(2);
    });

    it('does not persist when validation fails before run()', function () {
        $service = new class extends ApplicationService
        {
            public string $value = '';

            protected function rules(): array
            {
                return ['value' => 'required|string|min:3'];
            }

            public function run(): mixed
            {
                DB::table('test_records')->insert(['value' => $this->value]);

                return $this->value;
            }
        };

        $service->save();
        expect(DB::table('test_records')->count())->toBe(0);
    });
});

describe('call()', function () {
    it('persists data when run() succeeds', function () {
        (new PersistService(value: 'hello'))->call();
        expect(DB::table('test_records')->count())->toBe(1);
        expect(DB::table('test_records')->value('value'))->toBe('hello');
    });

    it('does not roll back on exception — partial writes survive', function () {
        $service = new class extends ApplicationService
        {
            protected function rules(): array
            {
                return [];
            }

            public function run(): mixed
            {
                DB::table('test_records')->insert(['value' => 'written']);
                throw new RuntimeException('failure after write');
            }
        };

        expect(fn () => $service->call())->toThrow(RuntimeException::class);
        // call() has no transaction — the insert already committed before the exception
        expect(DB::table('test_records')->count())->toBe(1);
    });

    it('partial inserts survive when run() throws mid-way', function () {
        $service = new class extends ApplicationService
        {
            protected function rules(): array
            {
                return [];
            }

            public function run(): mixed
            {
                DB::table('test_records')->insert(['value' => 'first']);
                DB::table('test_records')->insert(['value' => 'second']);
                throw new RuntimeException('mid-way failure');
            }
        };

        expect(fn () => $service->call())->toThrow(RuntimeException::class);
        // unlike save(), call() leaves both inserts committed
        expect(DB::table('test_records')->count())->toBe(2);
    });

    it('does not persist when validation fails before run()', function () {
        $service = new class extends ApplicationService
        {
            public string $value = '';

            protected function rules(): array
            {
                return ['value' => 'required|string|min:3'];
            }

            public function run(): mixed
            {
                DB::table('test_records')->insert(['value' => $this->value]);

                return $this->value;
            }
        };

        $service->call();
        expect(DB::table('test_records')->count())->toBe(0);
    });
});
