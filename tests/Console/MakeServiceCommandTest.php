<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(app_path('Services'));
});

it('creates the service file in app/Services', function () {
    $this->artisan('make:service', ['name' => 'CreateUser'])
        ->assertSuccessful();

    expect(file_exists(app_path('Services/CreateUser.php')))->toBeTrue();
});

it('generated file has the correct namespace', function () {
    $this->artisan('make:service', ['name' => 'CreateUser'])->assertSuccessful();

    expect(file_get_contents(app_path('Services/CreateUser.php')))
        ->toContain('namespace App\Services;');
});

it('generated file extends ApplicationService', function () {
    $this->artisan('make:service', ['name' => 'CreateUser'])->assertSuccessful();

    expect(file_get_contents(app_path('Services/CreateUser.php')))
        ->toContain('class CreateUser extends ApplicationService');
});

it('generated file imports ApplicationService', function () {
    $this->artisan('make:service', ['name' => 'CreateUser'])->assertSuccessful();

    expect(file_get_contents(app_path('Services/CreateUser.php')))
        ->toContain('use BruggeMatheus\ServiceLayer\ApplicationService;');
});

it('generated file contains rules() and run() methods', function () {
    $this->artisan('make:service', ['name' => 'CreateUser'])->assertSuccessful();

    $content = file_get_contents(app_path('Services/CreateUser.php'));
    expect($content)
        ->toContain('protected function rules(): array')
        ->toContain('public function run(): mixed');
});

it('supports nested namespaces', function () {
    $this->artisan('make:service', ['name' => 'Orders/PlaceOrder'])->assertSuccessful();

    expect(file_exists(app_path('Services/Orders/PlaceOrder.php')))->toBeTrue();

    expect(file_get_contents(app_path('Services/Orders/PlaceOrder.php')))
        ->toContain('namespace App\Services\Orders;')
        ->toContain('class PlaceOrder extends ApplicationService');
});

it('does not overwrite an existing file', function () {
    $this->artisan('make:service', ['name' => 'CreateUser'])->assertSuccessful();

    file_put_contents(app_path('Services/CreateUser.php'), '<?php // custom content');

    $this->artisan('make:service', ['name' => 'CreateUser'])
        ->expectsOutputToContain('already exists');

    expect(file_get_contents(app_path('Services/CreateUser.php')))
        ->toBe('<?php // custom content');
});
