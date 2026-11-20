<?php

namespace App\Http\Requests\Catalog;

use App\Actions\Catalog\TitleData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TitleRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }

    public function toData(): TitleData
    {
        return new TitleData(
            name: $this->string('name')->toString(),
            description: $this->string('description')->toString(),
        );
    }
}
