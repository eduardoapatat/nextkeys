<?php

namespace App\Actions\Catalog;

use App\Models\Title;

final class CreateTitle
{
    public function handle(TitleData $data): Title
    {
        return Title::query()->create([
            'name' => $data->name,
            'description' => $data->description,
        ]);
    }
}
