<?php

use BruggeMatheus\ServiceLayer\ApplicationService;
use Illuminate\Support\MessageBag;

// ── Stubs ────────────────────────────────────────────────────────────────────

class OrderService extends ApplicationService
{
    public int $runCalled = 0;

    public function __construct(
        public readonly int $quantity,
        public readonly int $stock,
        public readonly bool $customerActive = true,
    ) {}

    protected function rules(): array
    {
        return ['quantity' => ['required', 'integer', 'min:1']];
    }

    protected function validateStockAvailability(): void
    {
        if ($this->stock < $this->quantity) {
            $this->addError('quantity', 'Insufficient stock.');
        }
    }

    protected function validateCustomerActive(): void
    {
        if (! $this->customerActive) {
            $this->addError('customer', 'Customer is inactive.');
        }
    }

    public function run(): mixed
    {
        $this->runCalled++;

        return 'ok';
    }
}

class NoCustomValidationService extends ApplicationService
{
    protected function rules(): array
    {
        return [];
    }

    public function run(): mixed
    {
        return 'ok';
    }
}

// Service where a validate*() method name starts with validate but is in parent — should NOT run
class ParentValidateService extends ApplicationService
{
    public bool $parentValidateCalled = false;

    protected function rules(): array
    {
        return [];
    }

    public function run(): mixed
    {
        return 'ok';
    }
}

class ChildValidateService extends ParentValidateService
{
    public bool $childValidateCalled = false;

    protected function validateSomething(): void
    {
        $this->childValidateCalled = true;
    }
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('custom validate*() methods', function () {
    it('runs custom validate*() methods automatically', function () {
        $service = new OrderService(quantity: 10, stock: 5);
        $service->save();
        expect($service->errors()->has('quantity'))->toBeTrue();
    });

    it('runs all validate*() methods even when one fails', function () {
        $service = new OrderService(quantity: 10, stock: 5, customerActive: false);
        $service->save();
        expect($service->errors()->has('quantity'))->toBeTrue();
        expect($service->errors()->has('customer'))->toBeTrue();
    });

    it('accumulates errors from rules() and validate*() together', function () {
        // quantity: 0 fails rules() min:1, stock: 0 < 1 fails validateStockAvailability
        $service = new OrderService(quantity: 0, stock: 0, customerActive: false);
        $service->save();
        expect($service->errors()->count())->toBeGreaterThanOrEqual(2);
        expect($service->errors()->has('quantity'))->toBeTrue();
        expect($service->errors()->has('customer'))->toBeTrue();
    });

    it('does not call run() when a custom validation fails', function () {
        $service = new OrderService(quantity: 10, stock: 5);
        $service->save();
        expect($service->runCalled)->toBe(0);
    });

    it('calls run() when all custom validations pass', function () {
        $service = new OrderService(quantity: 3, stock: 10);
        $service->save();
        expect($service->runCalled)->toBe(1);
    });

    it('does not run custom validations when service has none', function () {
        $service = new NoCustomValidationService;
        expect($service->save())->toBe('ok');
    });

    it('only runs validate*() methods declared in the subclass, not in parents', function () {
        $service = new ChildValidateService;
        $service->save();
        expect($service->childValidateCalled)->toBeTrue();
        expect($service->parentValidateCalled)->toBeFalse();
    });
});

describe('addError()', function () {
    it('adds an error to the MessageBag', function () {
        $service = new OrderService(quantity: 10, stock: 5);
        $service->save();
        expect($service->errors())->toBeInstanceOf(MessageBag::class);
        expect($service->errors()->get('quantity'))->not->toBeEmpty();
    });

    it('multiple addError() calls accumulate on the same field', function () {
        $service = new class extends ApplicationService
        {
            protected function rules(): array
            {
                return [];
            }

            protected function validateDouble(): void
            {
                $this->addError('field', 'First error.');
                $this->addError('field', 'Second error.');
            }

            public function run(): mixed
            {
                return 'ok';
            }
        };

        $service->save();
        expect($service->errors()->get('field'))->toHaveCount(2);
    });

    it('errors from addError() and rules() are all accessible via errors()', function () {
        $service = new OrderService(quantity: 0, stock: 0, customerActive: false);
        $service->save();

        $all = $service->errors()->all();
        expect($all)->not->toBeEmpty();
        expect(count($all))->toBeGreaterThanOrEqual(2);
    });
});

describe('validate*() with save() and call()', function () {
    it('custom validations run with save()', function () {
        $service = new OrderService(quantity: 10, stock: 1);
        $service->save();
        expect($service->errors()->isNotEmpty())->toBeTrue();
    });

    it('custom validations run with call()', function () {
        $service = new OrderService(quantity: 10, stock: 1);
        $service->call();
        expect($service->errors()->isNotEmpty())->toBeTrue();
    });
});
