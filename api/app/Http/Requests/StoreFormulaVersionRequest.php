<?php

namespace App\Http\Requests;

use App\Exceptions\CircularDependencyException;
use App\Exceptions\ParseException;
use App\Exceptions\UndefinedVariableException;
use App\Services\FormulaValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFormulaVersionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // If standard validation failed, don't run the complex formula validation
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $expression = $this->input('expression');
                $variables = $this->input('variables', []);

                try {
                    app(FormulaValidator::class)->validate($expression, $variables);
                } catch (ParseException | UndefinedVariableException | CircularDependencyException $e) {
                    $validator->errors()->add('expression', $e->getMessage());
                }
            }
        ];
    }
}
