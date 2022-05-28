<?php

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\Validator;

class Action
{
    public function validate(array $data, array $rules, array $messages = [], array $customAttributes = []): Validator
    {
        $factory = app(ValidationFactory::class);

        return $factory->make($data, $rules, $messages, $customAttributes);
    }
}
