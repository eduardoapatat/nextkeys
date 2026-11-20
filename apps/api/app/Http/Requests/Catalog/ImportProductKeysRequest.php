<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Checks only the upload: a text file of at most 1 MB. The rules of its
 * contents (format, duplicates, limit of codes) live in ImportProductKeys.
 */
class ImportProductKeysRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:csv,txt', 'mimetypes:text/plain,text/csv,application/csv', 'max:1024'],
        ];
    }

    public function contents(): string
    {
        $file = $this->file('file');

        if (! $file instanceof UploadedFile) {
            throw new \LogicException('The file rule guarantees a single uploaded file.');
        }

        return $file->get() ?: '';
    }
}
