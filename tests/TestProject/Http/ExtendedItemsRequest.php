<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use Illuminate\Contracts\Validation\Rule;


class ExtendedItemsRequest extends ItemsRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array<string|Rule>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'names' => 'nullable|array',
            'names.*' => 'string',
        ]);
    }
}
