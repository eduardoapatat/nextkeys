<x-mail::message>
# ¡Gracias por tu compra!

Recibimos el pago de tu pedido **{{ $reference }}**@if ($paidAt) el {{ $paidAt }}@endif.

@foreach ($lines as $line)
## {{ $line['title'] }}

{{ $line['type'] }} · {{ $line['quantity'] }} × {{ $line['unitPrice'] }} = **{{ $line['lineTotal'] }}**

@if ($line['ships'])
Se despachará; te avisaremos cuando salga.
@else
@if ($line['activation'])
Actívala en: {{ $line['activation'] }}
@endif

@foreach ($line['codes'] as $code)
- `{{ $code }}`
@endforeach
@endif

@endforeach
@if ($shippingCost)
Envío: {{ $shippingCost }}

@endif
**Total: {{ $total }} (CLP)**

@if ($shippingAddress)
**Despacho a:** {{ $shippingAddress['recipient'] }}, {{ $shippingAddress['line'] }}.
@endif

<x-mail::button :url="$libraryUrl">
Ver en Mi biblioteca
</x-mail::button>

@if ($receipt)
<x-mail::panel>
**Comprobante de pago (Webpay)**<br>
Monto: {{ $receipt['amount'] }}<br>
@if ($receipt['paymentType'])
Tipo de pago: {{ $receipt['paymentType'] }}<br>
@endif
@if ($receipt['card'])
Tarjeta: {{ $receipt['card'] }}<br>
@endif
@if ($receipt['authorizationCode'])
Código de autorización: {{ $receipt['authorizationCode'] }}<br>
@endif
@if ($receipt['date'])
Fecha: {{ $receipt['date'] }}
@endif
</x-mail::panel>
@endif

Las claves digitales entregadas no admiten derecho a retracto (exclusión informada antes del pago, art. 3 bis letra b) de la Ley 19.496 y Decreto 52 de 2024). Si una clave no funciona, mantienes tu garantía legal: responde este correo.
@if ($hasPhysicalItems)
Para los productos físicos tienes 10 días de derecho a retracto desde que los recibes.
@endif

Guarda este correo: es tu comprobante de compra.

{{ config('app.name') }}

<small>Tienda ficticia de portafolio: ningún cobro es real.</small>
</x-mail::message>
