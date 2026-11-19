<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;


class ProfileRequest extends FormRequest
{
    /**
     * @return array<string, Rule|array<string|Rule>|string>
     */
    public function rules(): array
    {
        return [
            /**
             * Shown instead of the name.
             *
             * @example Ed
             * @deprecated Use display_name.
             */
            'nickname' => 'nullable|string',

            /**
             * Public name.
             *
             * @example Edward
             * @deprecated
             */
            'display_name' => 'required|nullable|string',
        ];
    }
}
