<?php

namespace App\Actions;

use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\Validator;

abstract class Action
{
    public function validate(array $data, array $rules, array $messages = [], array $attributes = []): Validator
    {
        return $this->validationFactory()
            ->make(
                data: $data,
                rules: $rules,
                messages: $messages,
                attributes: $attributes,
            );
    }

    private function validationFactory(): ValidationFactory
    {
        return app(ValidationFactory::class);
    }

    abstract public function execute(): void;
}
