<?php

it('calls run() after successful validation', function () {
    $service = new SpyService(name: 'Matheus');
    $service->save();
    expect($service->runCalled)->toBeTrue();
});

it('run() can return null', function () {
    expect((new NullService)->save())->toBeNull();
});

it('run() can return a scalar', function () {
    expect((new SumService(a: 10, b: 20))->save())->toBe(30);
});

it('run() can return a string', function () {
    expect((new EmailService(email: 'a@b.com'))->save())->toBe('a@b.com');
});

it('run() can return an array', function () {
    $result = (new PersonService(name: 'Mat', email: 'a@b.com', age: 20))->save();
    expect($result)->toBeArray()->toHaveKeys(['name', 'email', 'age']);
});

it('exceptions from run() bubble up unchanged', function () {
    expect(fn () => (new ThrowingService)->save())
        ->toThrow(RuntimeException::class, 'Forced failure');
});

it('can be called from a Job context (plain method call)', function () {
    $job = new class
    {
        public function handle(): mixed
        {
            return (new SumService(a: 5, b: 5))->save();
        }
    };

    expect($job->handle())->toBe(10);
});

it('can be called from a service container binding', function () {
    app()->bind('sum', fn () => new SumService(a: 3, b: 7));
    expect(app('sum')->save())->toBe(10);
});
