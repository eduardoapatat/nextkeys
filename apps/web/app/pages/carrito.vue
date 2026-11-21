<script setup lang="ts">
useHead({ title: 'Carrito' });

const {
    cart,
    lines,
    total,
    pending,
    ready: canCheckout,
} = await useCartProducts();
</script>

<template>
    <main>
        <h1>Carrito</h1>

        <p v-if="lines.length === 0">
            Tu carrito está vacío. <NuxtLink to="/">Ver el catálogo</NuxtLink>
        </p>
        <template v-else>
            <table>
                <thead>
                    <tr>
                        <th scope="col">Producto</th>
                        <th scope="col">Precio</th>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Subtotal</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in lines" :key="line.slug">
                        <template v-if="line.product">
                            <td>
                                <NuxtLink :to="`/${line.slug}`">
                                    {{ line.product.title.name }}
                                </NuxtLink>
                                <br />
                                <small>{{ variantLabel(line.product) }}</small>
                                <template v-if="!line.available">
                                    <br /><strong>No disponible</strong> (sin
                                    stock)
                                </template>
                            </td>
                            <td>{{ formatClp(line.product.price) }}</td>
                        </template>
                        <template v-else>
                            <td>
                                {{ line.slug }}<br /><strong
                                    >No disponible</strong
                                >
                                (ya no está a la venta)
                            </td>
                            <td>—</td>
                        </template>
                        <td>
                            <select
                                :name="`quantity-${line.slug}`"
                                :aria-label="`Cantidad de ${line.product?.title.name ?? line.slug}`"
                                :value="line.quantity"
                                @change="
                                    cart.update(
                                        line.slug,
                                        Number(
                                            ($event.target as HTMLSelectElement)
                                                .value,
                                        ),
                                    )
                                "
                            >
                                <option
                                    v-for="n in maxQuantityPerLine"
                                    :key="n"
                                    :value="n"
                                >
                                    {{ n }}
                                </option>
                            </select>
                        </td>
                        <td>
                            {{
                                line.product
                                    ? formatClp(
                                          line.product.price * line.quantity,
                                      )
                                    : '—'
                            }}
                        </td>
                        <td>
                            <button
                                type="button"
                                :aria-label="`Quitar ${line.product?.title.name ?? line.slug}`"
                                @click="cart.remove(line.slug)"
                            >
                                Quitar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p>
                Total estimado: <strong>{{ formatClp(total) }}</strong>
                <br />
                <small>
                    Los precios y el stock se confirman al crear el pedido.
                </small>
            </p>

            <p v-if="canCheckout">
                <NuxtLink to="/checkout">Ir a pagar</NuxtLink>
            </p>
            <p v-else-if="!pending">
                Quita los productos no disponibles para continuar.
            </p>
        </template>
    </main>
</template>
