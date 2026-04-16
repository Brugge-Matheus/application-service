<?php

namespace BruggeMatheus\ServiceLayer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

abstract class ApplicationService
{
    private MessageBag $errors;

    abstract protected function rules(): array;

    abstract public function run(): mixed;

    private function validate(): ?array
    {
        $data = collect(get_object_vars($this))->except('errors')->toArray();

        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            $this->errors = $validator->errors();

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
