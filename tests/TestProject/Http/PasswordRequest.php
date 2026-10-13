<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;


class PasswordRequest extends FormRequest
{
    /**
     * @return array<string, Rule|array<string|Rule>|string>
     */
    public function rules(): array
    {
        return [
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'confirmed', Password::min(9)],
        ];
    }
}
