# ApplicationService

Tiny on code, heavy on concept. ApplicationService is an opinionated Service Layer pattern for Laravel.

Inspired by the Ruby gem [application_action](https://github.com/Rudiney/application_action) — the concept is very simple, a clean contract for encapsulating business logic in dedicated classes that validate and execute.

## Installation

```bash
composer require brugge-matheus/application-service
```

## Generating a service

The package registers an artisan command to scaffold a new service:

```bash
php artisan make:service CreateUser
# → app/Services/CreateUser.php

php artisan make:service Orders/PlaceOrder
# → app/Services/Orders/PlaceOrder.php
```

The generated file already extends `ApplicationService` with the correct namespace, an empty constructor, `rules()`, and `run()` — ready to fill in.

## Usage

Extend `ApplicationService`, declare your inputs as `public` properties, define the validation rules, and implement `run()`.

`app/Services/CreateUser.php`

```php
use BruggeMatheus\ServiceLayer\ApplicationService;

class CreateUser extends ApplicationService
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}

    protected function rules(): array
    {
        return [
            'name'  => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users'],
        ];
    }

    public function run(): mixed
    {
        return User::create([
            'name'  => $this->name,
            'email' => $this->email,
        ]);
    }
}
```

### `save()` — with database transaction

Use `save()` when your service writes to the database. The entire `run()` executes inside a `DB::transaction()`, so any exception rolls back all changes.

```php
$service = new CreateUser(name: 'Matheus', email: 'matheus@email.com');
$result  = $service->save();

if ($service->errors()->isNotEmpty()) {
    return response()->json(['error' => $service->errors()->first()], 422);
}

return response()->json($result, 201);
```

### `call()` — without database transaction

Use `call()` when your service does not write to the database — sending notifications, calling external APIs, dispatching jobs, etc. Validation still runs, but there is no wrapping transaction.

```php
$service = new NotifyUser(user: $user, message: $message);
$result  = $service->call();

if ($service->errors()->isNotEmpty()) {
    return response()->json(['error' => $service->errors()->first()], 422);
}
```

> **Warning:** with `call()`, if `run()` throws after a partial write, there is no rollback. Prefer `save()` for anything that touches the database.

### Checking errors

Both methods return `['status' => false, 'message' => '...']` on validation failure — mirroring Rails' `save` behavior rather than throwing an exception. The full `MessageBag` is also available via `errors()`.

```php
$service = new CreateUser(name: '', email: 'not-an-email');
$result  = $service->save();

// $result => ['status' => false, 'message' => 'The name field is required.']

$service->errors()->all();
// => ['The name field is required.', 'The email field must be a valid email address.']

$service->errors()->has('email'); // true
```

> **Note on property visibility:** Laravel's `Validator` receives input collected via `get_object_vars($this)` from the parent class scope. This means properties must be declared as `public` or `protected` — `private` properties on the subclass are invisible to the parent and will never reach the validator.

### Custom validations with `validate*()`

For logic that goes beyond what Laravel's rule strings can express, declare `protected` methods prefixed with `validate` in your service. They are discovered and executed automatically — every one of them runs regardless of whether a previous one already failed, so all errors are accumulated at once.

```php
class PlaceOrder extends ApplicationService
{
    public function __construct(
        public readonly User    $customer,
        public readonly Product $product,
        public readonly int     $quantity,
    ) {}

    protected function rules(): array
    {
        return ['quantity' => ['required', 'integer', 'min:1']];
    }

    protected function validateStockAvailability(): void
    {
        if ($this->product->stock < $this->quantity) {
            $this->addError(
                'quantity',
                "Insufficient stock. Available: {$this->product->stock}."
            );
        }
    }

    protected function validateCustomerActive(): void
    {
        if (! $this->customer->is_active) {
            $this->addError('customer', 'Customer is inactive.');
        }
    }

    public function run(): mixed
    {
        // only reaches here if rules() and all validate*() pass
    }
}
```

Use `$this->addError(field, message)` inside any `validate*()` method to register an error. Errors from `rules()` and from `validate*()` methods are merged into the same `MessageBag` and are all accessible via `errors()`.

## Concept

A `Service` represents a single, named operation your application performs. Not a model, not a controller — something in between. It has a clear input, a clear contract, and a single responsibility: validate and execute.

Laravel already gives you a lot. `Eloquent` handles persistence. Controllers handle HTTP. `Jobs` handle async work. But none of them is the right home for your business logic — the code that says *what your application actually does*. That's where `ApplicationService` fits.

#### One class, one operation

Each `Service` does one thing. `CreateOrder`, `CancelSubscription`, `ProcessRefund`. The name tells you exactly what happens when it runs. No side effects outside its scope, no hidden behavior.

#### Validation is part of the contract

Rules are declared inside the `Service` itself via `rules()`. This is intentional — the validation belongs to the operation, not to the model. A `User` model might require an email to be present always, but only `InviteUser` requires it to be unique among pending invitations. These are different concerns.

For logic that goes beyond what rule strings can express — cross-field checks, database lookups, external state — declare `protected` methods prefixed with `validate`. They are auto-discovered and all of them run before `run()` is ever called, accumulating every error at once. The caller sees the full picture in a single response, not one error at a time.

#### `save()` vs `call()` — choose based on what you're doing

- Use **`save()`** when `run()` touches the database. The entire execution is wrapped in a `DB::transaction()`, so a failure at any point rolls everything back. Your database stays consistent.
- Use **`call()`** when `run()` does not write to the database — sending emails, calling external APIs, publishing events. Validation still runs, but there is no transaction overhead.

The distinction is explicit by design. The caller knows exactly what kind of operation they are invoking.

#### The same service, any entry point

A `Service` has no knowledge of HTTP, queues, or CLI. This means the exact same class can be called from a controller, a `Job`, an artisan command, or tinker — without changing a single line of its implementation. The entry point becomes irrelevant.

This makes testing trivial: no HTTP stack, no queue infrastructure. Just instantiate, call, assert.

### A Practical Example

Consider an e-commerce app where a customer places an order. The flow involves validating stock, charging the card, and notifying the customer.

```php
class PlaceOrder extends ApplicationService
{
    public function __construct(
        public readonly User    $customer,
        public readonly Product $product,
        public readonly int     $quantity,
    ) {}

    protected function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function validateStockAvailability(): void
    {
        if ($this->product->stock < $this->quantity) {
            $this->addError(
                'quantity',
                "Insufficient stock. Available: {$this->product->stock}."
            );
        }
    }

    protected function validateCustomerActive(): void
    {
        if (! $this->customer->is_active) {
            $this->addError('customer', 'Customer is inactive.');
        }
    }

    public function run(): mixed
    {
        $order = Order::create([
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => $this->quantity,
            'total' => $this->product->price * $this->quantity,
        ]);

        $this->product->decrement('stock', $this->quantity);

        (new ChargeCustomer(order: $order))->save();

        return $order;
    }
}
```

```php
// From a controller
$service = new PlaceOrder(customer: $user, product: $product, quantity: 2);
$order   = $service->save();

if ($service->errors()->isNotEmpty()) {
    return response()->json(['error' => $service->errors()->first()], 422);
}

// From a Job
class PlaceOrderJob implements ShouldQueue
{
    public function handle(): void
    {
        (new PlaceOrder(
            customer: $this->customer,
            product: $this->product,
            quantity: $this->quantity,
        ))->save();
    }
}

// From artisan tinker
(new PlaceOrder(customer: User::first(), product: Product::first(), quantity: 1))->save();
```

Note how the same `Service` is called identically from a controller, a job, or the console. The entry point is irrelevant.

## License

MIT
