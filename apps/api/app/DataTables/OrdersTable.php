<?php

namespace App\DataTables;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use InertiaDataTable\Column;
use InertiaDataTable\DataTable;

/**
 * Orders of the admin panel: filter by status, search by reference or
 * customer email (docs/domain/orders.md, "Admin panel").
 */
class OrdersTable extends DataTable
{
    /**
     * @return Builder<Order>
     */
    public function query(): Builder
    {
        return Order::query()->select('orders.*');
    }

    /**
     * @return list<Column>
     */
    public function columns(): array
    {
        return [
            Column::text('reference', 'Referencia')->searchable()->sortable()->hideable(false),
            Column::text('customer', 'Cliente')->from('user.name')->sortable(),
            Column::text('email', 'Email')->from('user.email')->searchable(),
            Column::option('status', 'Estado')->options(OrderStatus::class)->badge()->sortable()->filterMultiSelect(),
            Column::number('total', 'Total')->money('CLP')->sortable(),
            Column::date('created_at', 'Creado')->format('datetime')->sortable(),
            Column::date('expires_at', 'Vence')->format('datetime')->hidden(),
        ];
    }

    /**
     * The row link needs the reference even when its column is hidden.
     *
     * @return list<string>
     */
    public function alwaysInclude(): ?array
    {
        return ['reference'];
    }

    public function defaultSort(): ?string
    {
        return '-created_at';
    }
}
