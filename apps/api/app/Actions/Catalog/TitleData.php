<?php

namespace App\Actions\Catalog;

final readonly class TitleData
{
    public function __construct(
        public string $name,
        public string $description,
    ) {}
}
