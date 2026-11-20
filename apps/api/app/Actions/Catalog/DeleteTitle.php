<?php

namespace App\Actions\Catalog;

use App\Models\Title;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeleteTitle
{
    /**
     * Deletes a title with no products. The title row is locked first, so a
     * product created concurrently (its insert locks the title row too) is
     * either seen by the check or waits until the delete is done.
     *
     * @throws ValidationException
     */
    public function handle(Title $title): void
    {
        DB::transaction(function () use ($title): void {
            Title::query()->whereKey($title->id)->lockForUpdate()->first();

            if ($title->products()->exists()) {
                throw ValidationException::withMessages([
                    'title' => 'Este título tiene productos y no se puede eliminar.',
                ]);
            }

            $title->delete();
        });
    }
}
