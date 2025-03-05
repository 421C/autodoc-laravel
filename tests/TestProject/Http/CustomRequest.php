<?php

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Rule;


class CustomRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array<string|Rule>|string>
     */
    public function rules(): array
    {
        return [
            'items' => 'required|array',

            'items.*.id' => $this->getItemIdRule(),

            /**
             * @var object
             */
            'items.*.data' => 'required',
        ];
    }


    private function getItemIdRule(): string {
        return 'integer';
    }
}
