<x-mail::message>
# Tu pedido va en camino

Despachamos los productos físicos de tu pedido **{{ $reference }}** con **{{ $carrier }}**.

Número de seguimiento: **{{ $trackingNumber }}**

<x-mail::button :url="$trackingUrl">
Seguir mi envío
</x-mail::button>

@foreach ($lines as $line)
- {{ $line['quantity'] }} × {{ $line['title'] }}
@endforeach

**Despacho a:** {{ $recipient }}, {{ $addressLine }}.

Tienes {{ $withdrawalDays }} días de derecho a retracto desde que recibes los productos físicos. Puedes ver el estado del envío en [tu pedido]({{ $orderUrl }}).

{{ config('app.name') }}

<small>Tienda ficticia de portafolio: ningún envío es real.</small>
</x-mail::message>
