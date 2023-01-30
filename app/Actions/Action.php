<?php

namespace App\Actions;

use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\Validator;

abstract class Action
{
    public function validate(array $data, array $rules, array $messages = [], array $customAttributes = []): Validator
    {
        $factory = app(ValidationFactory::class);

        return $factory->make($data, $rules, $messages, $customAttributes);
    }

    abstract public function execute();
}
