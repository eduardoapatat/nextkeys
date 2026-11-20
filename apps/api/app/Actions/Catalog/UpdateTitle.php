<?php

namespace App\Actions\Catalog;

use App\Models\Title;

final class UpdateTitle
{
    /**
     * Renaming a title never changes the slugs of its products.
     */
    public function handle(Title $title, TitleData $data): Title
    {
        $title->update([
            'name' => $data->name,
            'description' => $data->description,
        ]);

        return $title;
    }
}
