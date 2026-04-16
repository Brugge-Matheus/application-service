<?php

use BruggeMatheus\ServiceLayer\ApplicationService;
use Illuminate\Support\MessageBag;

// Validation behavior is identical for save() and call() — both go through validate()
// These tests ensure neither method introduces a silent regression in validation

describe('save()', function () {
    it('passes validation and returns run() result', function () {
        expect((new SumService(a: 2, b: 3))->save())->toBe(5);
    });

    it('fails validation and returns status false', function () {
        $result = (new EmailService(email: 'not-an-email'))->save();
        expect($result['status'])->toBeFalse();
    });

    it('returns a non-empty message on failure', function () {
        $result = (new EmailService(email: 'not-an-email'))->save();
        expect($result['message'])->toBeString()->not->toBeEmpty();
    });

    it('message matches the first error in the MessageBag', function () {
        $service = new EmailService(email: 'bad');
        $result  = $service->save();
        expect($result['message'])->toBe($service->errors()->first());
    });

    it('errors() returns empty MessageBag before calling', function () {
        $service = new SumService(a: 1, b: 2);
        expect($service->errors())->toBeInstanceOf(MessageBag::class);
        expect($service->errors()->isEmpty())->toBeTrue();
    });

    it('errors() remains empty after successful call', function () {
        $service = new SumService(a: 1, b: 2);
        $service->save();
        expect($service->errors()->isEmpty())->toBeTrue();
    });

    it('errors() is filled after validation failure', function () {
        $service = new EmailService(email: 'bad');
        $service->save();
        expect($service->errors()->isNotEmpty())->toBeTrue();
    });

    it('errors() contains the exact field key that failed', function () {
        $service = new EmailService(email: 'bad');
        $service->save();
        expect($service->errors()->has('email'))->toBeTrue();
    });

    it('errors() contains all failed field keys', function () {
        $service = new PersonService(name: 'A', email: 'bad', age: 10);
        $service->save();
        expect($service->errors()->has('name'))->toBeTrue();
        expect($service->errors()->has('email'))->toBeTrue();
        expect($service->errors()->has('age'))->toBeTrue();
    });

    it('does not call run() when validation fails', function () {
        $service = new SpyService(name: '');
        $service->save();
        expect($service->runCalled)->toBeFalse();
    });
});

describe('call()', function () {
    it('passes validation and returns run() result', function () {
        expect((new SumService(a: 2, b: 3))->call())->toBe(5);
    });

    it('fails validation and returns status false', function () {
        $result = (new EmailService(email: 'not-an-email'))->call();
        expect($result['status'])->toBeFalse();
    });

    it('returns a non-empty message on failure', function () {
        $result = (new EmailService(email: 'not-an-email'))->call();
        expect($result['message'])->toBeString()->not->toBeEmpty();
    });

    it('message matches the first error in the MessageBag', function () {
        $service = new EmailService(email: 'bad');
        $result  = $service->call();
        expect($result['message'])->toBe($service->errors()->first());
    });

    it('errors() returns empty MessageBag before calling', function () {
        $service = new SumService(a: 1, b: 2);
        expect($service->errors())->toBeInstanceOf(MessageBag::class);
        expect($service->errors()->isEmpty())->toBeTrue();
    });

    it('errors() remains empty after successful call', function () {
        $service = new SumService(a: 1, b: 2);
        $service->call();
        expect($service->errors()->isEmpty())->toBeTrue();
    });

    it('errors() is filled after validation failure', function () {
        $service = new EmailService(email: 'bad');
        $service->call();
        expect($service->errors()->isNotEmpty())->toBeTrue();
    });

    it('errors() contains the exact field key that failed', function () {
        $service = new EmailService(email: 'bad');
        $service->call();
        expect($service->errors()->has('email'))->toBeTrue();
    });

    it('errors() contains all failed field keys', function () {
        $service = new PersonService(name: 'A', email: 'bad', age: 10);
        $service->call();
        expect($service->errors()->has('name'))->toBeTrue();
        expect($service->errors()->has('email'))->toBeTrue();
        expect($service->errors()->has('age'))->toBeTrue();
    });

    it('does not call run() when validation fails', function () {
        $service = new SpyService(name: '');
        $service->call();
        expect($service->runCalled)->toBeFalse();
    });
});

// private properties of the subclass are invisible to get_object_vars()
// when called from the parent scope — validator receives empty data
// this applies equally to save() and call() since both go through validate()
it('private subclass properties are invisible to the validator', function () {
    $service = new class ('valid@email.com') extends ApplicationService
    {
        public function __construct(private readonly string $email) {}

        protected function rules(): array
        {
            return ['email' => 'required|email'];
        }

        public function run(): mixed
        {
            return $this->email;
        }
    };

    foreach (['save', 'call'] as $method) {
        $result = $service->$method();
        expect($result['status'])->toBeFalse();
        expect($result['message'])->toContain('required');
    }
});
