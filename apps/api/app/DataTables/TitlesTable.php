<?php

namespace App\DataTables;

use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use InertiaDataTable\Column;
use InertiaDataTable\DataTable;

class TitlesTable extends DataTable
{
    /**
     * @return Builder<Title>
     */
    public function query(): Builder
    {
        return Title::query()->withCount('products');
    }

    /**
     * @return list<Column>
     */
    public function columns(): array
    {
        return [
            Column::text('name', 'Nombre')->sortable()->hideable(false),
            Column::text('description', 'Descripción')->limit(80),
            Column::number('products_count', 'Productos'),
        ];
    }

    public function defaultSort(): ?string
    {
        return 'name';
    }
}
