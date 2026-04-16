<?php

namespace BruggeMatheus\ServiceLayer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use ReflectionClass;
use ReflectionMethod;

abstract class ApplicationService
{
    private MessageBag $errors;

    abstract protected function rules(): array;

    abstract public function run(): mixed;

    private function runCustomValidations(): void
    {
        $methods = (new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PROTECTED);

        foreach ($methods as $method) {
            if (str_starts_with($method->name, 'validate')
                && $method->getDeclaringClass()->getName() !== self::class) {
                $method->invoke($this);
            }
        }
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors ??= new MessageBag;
        $this->errors->add($field, $message);
    }

    private function validate(): ?array
    {
        $this->errors = new MessageBag;

        $data = collect(get_object_vars($this))->except('errors')->toArray();
        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            $this->errors->merge($validator->errors());
        }

        $this->runCustomValidations();

        if ($this->errors->isNotEmpty()) {
            return ['status' => false, 'message' => $this->errors->first()];
        }

        return null;
    }

    public function save(): mixed
    {
        if ($failure = $this->validate()) {
            return $failure;
        }

        return DB::transaction(fn () => $this->run());
    }

    public function call(): mixed
    {
        if ($failure = $this->validate()) {
            return $failure;
        }

        return $this->run();
    }

    public function errors(): MessageBag
    {
        return $this->errors ?? new MessageBag;
    }
}
