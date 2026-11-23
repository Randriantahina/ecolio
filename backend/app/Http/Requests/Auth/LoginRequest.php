<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Application\Identity\DTOs\LoginDto;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function toDto(): LoginDto
    {
        return new LoginDto(
            login: (string) $this->validated('login'),
            password: (string) $this->validated('password'),
            deviceName: (string) $this->validated('device_name', 'web'),
        );
    }
}
