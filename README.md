# Laravel Application Service

A base class for encapsulating business logic in Laravel, inspired by the Ruby [application_action](https://github.com/Rudiney/application_action) gem.

## Installation

```bash
composer require seu-vendor/laravel-application-service
```

## Usage

```php
use SeuVendor\LaravelServiceLayer\ServiceLayer;

class CreateUser extends ServiceLayer
{
    public function __construct(
        private readonly string $name,
        private readonly string $email,
    ) {}

    protected function rules(): array
    {
        return [
            'name'  => ['required', 'string'],
            'email' => ['required', 'email'],
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

// Uso
$service = new CreateUser(name: 'John', email: 'john@example.com');
$result  = $service->save();

// Verificando erros
if ($service->errors()->isNotEmpty()) {
    return $service->errors()->first();
}
```

## License

MIT
