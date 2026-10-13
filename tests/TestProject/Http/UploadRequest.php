<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests\TestProject\Http;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;


class UploadRequest extends FormRequest
{
    /**
     * @return array<string, Rule|array<string|Rule>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'max:2048'],
            'title' => 'string',
        ];
    }
}
