<script setup lang="ts">
import { fetchLibrary, revealKeys } from '~/api/library';
import type { LibraryItem, RevealedKey } from '~/api/schemas';

definePageMeta({ middleware: 'auth' });
useHead({ title: 'Mi biblioteca' });

const { data: library } = await useAsyncData('library', () => fetchLibrary());

/** Revealed codes, by line; they live only in this page, never in state. */
const revealed = ref<Record<string, RevealedKey[]>>({});
const errors = ref<string[]>([]);

function lineKey(item: LibraryItem): string {
    return `${item.order_reference}/${item.product.slug}`;
}

async function reveal(item: LibraryItem) {
    errors.value = [];

    try {
        revealed.value[lineKey(item)] = await revealKeys(
            item.order_reference,
            item.product.slug,
        );
    } catch (error) {
        errors.value = formErrorMessages(error);
    }
}

function activation(item: LibraryItem): string {
    return [
        item.product.activation_service?.name,
        item.product.platforms.map((platform) => platform.name).join(', '),
        item.product.region?.name,
    ]
        .filter(Boolean)
        .join(' · ');
}
</script>

<template>
    <main>
        <h1>Mi biblioteca</h1>
        <FormErrors :messages="errors" />

        <p v-if="library && library.data.length === 0">
            Aún no tienes claves. Cuando pagues un pedido aparecerán aquí.
        </p>

        <article v-for="item in library?.data" :key="lineKey(item)">
            <h2>{{ item.title_name }}</h2>
            <p>
                {{ item.type.name }} · {{ activation(item) }}<br />
                Pedido
                <NuxtLink :to="`/pedidos/${item.order_reference}`">{{
                    item.order_reference
                }}</NuxtLink>
                <template v-if="item.paid_at">
                    · pagado el {{ formatDateTime(item.paid_at) }}
                </template>
            </p>

            <ul v-if="revealed[lineKey(item)]">
                <li v-for="key in revealed[lineKey(item)]" :key="key.hint">
                    <code>{{ key.code }}</code>
                </li>
            </ul>
            <template v-else>
                <ul>
                    <li v-for="key in item.keys" :key="key.hint">
                        {{ key.hint }}
                        <template v-if="key.revealed"> (ya revelada)</template>
                    </li>
                </ul>
                <button
                    type="button"
                    data-action="reveal"
                    @click="reveal(item)"
                >
                    Mostrar {{ item.keys.length === 1 ? 'clave' : 'claves' }}
                </button>
            </template>
        </article>
    </main>
</template>
