<?php

namespace App\Http\Requests;

use App\Exceptions\CircularDependencyException;
use App\Exceptions\ParseException;
use App\Exceptions\UndefinedVariableException;
use App\Services\FormulaValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateFormulaVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'expression' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
            'variables.*.name' => ['required', 'string'],
            'variables.*.expression' => ['required', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $expression = $this->input('expression');
                $variables = $this->input('variables', []);

                try {
                    app(FormulaValidator::class)->validate($expression, $variables);
                } catch (ParseException|UndefinedVariableException|CircularDependencyException $e) {
                    $validator->errors()->add('expression', $e->getMessage());
                }
            },
        ];
    }
}
