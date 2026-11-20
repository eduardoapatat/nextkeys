<?php

namespace App\Enums;

/**
 * Why a line of a key import file was not imported.
 */
enum KeyImportRejectionReason: string
{
    case InvalidFormat = 'invalid_format';
    case DuplicateInFile = 'duplicate_in_file';
    case AlreadyExists = 'already_exists';

    public function label(): string
    {
        return match ($this) {
            self::InvalidFormat => 'Formato inválido: de 5 a 100 caracteres entre letras, números y guiones.',
            self::DuplicateInFile => 'Repetido en el archivo.',
            self::AlreadyExists => 'Ya existe en la base.',
        };
    }
}
