<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;


class ItemsRequest extends FormRequest
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


    private function getItemIdRule(): string
    {
        return 'integer';
    }
}
