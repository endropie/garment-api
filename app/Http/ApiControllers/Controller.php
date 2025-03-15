<?php

namespace App\Http\ApiControllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

abstract class Controller
{
    protected static $responseBuilder;
    protected static $errorFormatter;

    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = $this->getValidationFactory()->make($request->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            if (app()->runningInConsole()) {
                try {
                    $this->throwValidationException($request, $validator);
                } catch (\Illuminate\Validation\ValidationException $th) {
                    dump("Error In Validation", ["message" => $th->getMessage(), "errors" => $th->errors(), "request" => $request->all()]);
                }
            }
            else $this->throwValidationException($request, $validator);
        }

        return $this->extractInputFromRules($request, $rules);
    }

    protected function getValidationFactory()
    {
        return app('validator');
    }

    protected function extractInputFromRules(Request $request, array $rules)
    {
        return $request->only(collect($rules)->keys()->map(function ($rule) {
            return str($rule)->contains('.') ? explode('.', $rule)[0] : $rule;
        })->unique()->toArray());
    }

    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        if (isset(static::$responseBuilder)) {
            return (static::$responseBuilder)($request, $errors);
        }

        return new JsonResponse($errors, 422);
    }

    protected function formatValidationErrors(Validator $validator)
    {
        if (isset(static::$errorFormatter)) {
            return (static::$errorFormatter)($validator);
        }

        return $validator->errors()->getMessages();
    }

    protected function throwValidationException(Request $request, $validator)
    {
        throw new ValidationException($validator, $this->buildFailedValidationResponse(
            $request, $this->formatValidationErrors($validator)
        ));
    }
}
