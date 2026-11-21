<?php

/*
 * Spanish messages for the validation rules the admin panel and the storefront
 * API use. Rules not
 * listed here fall back to English (`fallback_locale`).
 */
return [
    'array' => 'El campo :attribute debe ser una lista.',
    'confirmed' => 'La confirmación del campo :attribute no coincide.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'distinct' => 'El campo :attribute tiene un valor repetido.',
    'enum' => 'El :attribute seleccionado no es válido.',
    'exists' => 'El :attribute seleccionado no existe.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
        'file' => 'El :attribute no puede pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'password' => [
        'letters' => 'La :attribute debe tener al menos una letra.',
        'mixed' => 'La :attribute debe tener al menos una mayúscula y una minúscula.',
        'numbers' => 'La :attribute debe tener al menos un número.',
        'symbols' => 'La :attribute debe tener al menos un símbolo.',
        'uncompromised' => 'Esta :attribute apareció en una filtración de datos. Elige otra.',
    ],
    'prohibited' => 'El campo :attribute no está permitido.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'dimensions' => 'El campo :attribute debe medir al menos :min_width × :min_height píxeles.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'extensions' => 'El :attribute debe tener una de estas extensiones: :values.',
    'file' => 'El :attribute debe ser un archivo.',
    'mimetypes' => 'El :attribute debe ser un archivo de texto (:values).',
    'uploaded' => 'No se pudo subir el :attribute.',
    'unique' => 'El :attribute ya está en uso.',

    'attributes' => [
        'cover' => 'carátula',
        'file' => 'archivo',
        'activation_service_id' => 'servicio de activación',
        'description' => 'descripción',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'is_published' => 'publicado',
        'name' => 'nombre',
        'platform_ids' => 'plataformas',
        'platform_ids.*' => 'plataforma',
        'price' => 'precio',
        'region_id' => 'región',
        'status' => 'estado',
        'slug' => 'slug',
        'stock' => 'stock',
        'title_id' => 'título',
        'type' => 'tipo',
    ],
];
