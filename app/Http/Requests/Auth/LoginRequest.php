<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (!auth()->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $this->throwValidationException('email', trans('auth.failed'));
        }
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!app()->environment('testing')) {
            $this->session()->limit(5)->by($this->ip())->allow(5)->then(fn() => $this->throwValidationException('email', trans('auth.throttle')));
        }
    }
}