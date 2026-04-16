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

    public function save(): mixed
    {
        $data = collect(get_object_vars($this))->except('errors')->toArray();

        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            $this->errors = $validator->errors();

            return ['status' => false, 'message' => $this->errors->first()];
        }

        return DB::transaction(fn () => $this->run());
    }

    public function errors(): MessageBag
    {
        return $this->errors ?? new MessageBag;
    }
}
