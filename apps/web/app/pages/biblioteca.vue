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

/** The library has no cover URL: it prints the generated cover. */
function coverOf(item: LibraryItem) {
    return {
        ...item.product,
        cover_url: null,
        type: item.type,
        title: { name: item.title_name },
    };
}

const copied = ref<string | null>(null);

async function copy(code: string) {
    await navigator.clipboard.writeText(code);
    copied.value = code;
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
    <main class="mx-auto max-w-4xl px-4 py-8 md:py-10">
        <h1 class="text-5xl text-chalk uppercase md:text-6xl">Mi biblioteca</h1>
        <p class="mt-2 text-chalk-muted">
            Las claves de tus pedidos pagados. Se muestran solo cuando las
            pides.
        </p>
        <FormErrors :messages="errors" class="mt-6" />

        <EmptyState
            v-if="library && library.data.length === 0"
            message="Aún no tienes claves. Cuando pagues un pedido aparecerán aquí."
            class="mt-8"
        >
            <UiButton :to="catalogLink()" variant="primary"
                >Ver el catálogo</UiButton
            >
        </EmptyState>

        <ul class="mt-8 flex flex-col gap-4">
            <li v-for="item in library?.data" :key="lineKey(item)">
                <article
                    class="grid grid-cols-[4.5rem_minmax(0,1fr)] gap-4 rounded-card border border-line bg-ink-2 p-4 sm:grid-cols-[6rem_minmax(0,1fr)] sm:gap-6 sm:p-5"
                >
                    <ProductCover :product="coverOf(item)" size="sm" />
                    <div class="flex min-w-0 flex-col gap-3">
                        <div>
                            <h2 class="text-2xl text-chalk uppercase">
                                {{ item.title_name }}
                            </h2>
                            <p class="text-sm text-chalk-muted">
                                {{ item.type.name }} · {{ activation(item) }}
                            </p>
                            <p class="text-sm text-chalk-faint">
                                Pedido
                                <NuxtLink
                                    :to="`/pedidos/${item.order_reference}`"
                                    class="font-mono text-crt hover:text-chalk"
                                    >{{ item.order_reference }}</NuxtLink
                                >
                                <template v-if="item.paid_at">
                                    · pagado el
                                    {{ formatDateTime(item.paid_at) }}
                                </template>
                            </p>
                        </div>

                        <ul
                            v-if="revealed[lineKey(item)]"
                            class="flex flex-col gap-2"
                        >
                            <li
                                v-for="key in revealed[lineKey(item)]"
                                :key="key.hint"
                                class="receipt flex flex-wrap items-center justify-between gap-3 px-4"
                            >
                                <code
                                    class="font-mono text-base font-semibold break-all text-paper-ink select-all"
                                    >{{ key.code }}</code
                                >
                                <button
                                    type="button"
                                    class="pressable font-sans text-sm text-paper-muted underline hover:text-paper-ink"
                                    @click="copy(key.code)"
                                >
                                    {{
                                        copied === key.code
                                            ? 'Copiada'
                                            : 'Copiar'
                                    }}
                                </button>
                            </li>
                        </ul>
                        <div
                            v-else
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <ul class="flex flex-wrap gap-2">
                                <li
                                    v-for="key in item.keys"
                                    :key="key.hint"
                                    class="rounded-control border border-dashed border-line-strong px-2 py-1 font-mono text-sm text-chalk-muted"
                                >
                                    {{ key.hint }}
                                    <template v-if="key.revealed">
                                        (ya revelada)</template
                                    >
                                </li>
                            </ul>
                            <UiButton
                                variant="primary"
                                size="sm"
                                data-action="reveal"
                                @click="reveal(item)"
                            >
                                <UiIcon name="key" :size="16" />Mostrar
                                {{
                                    item.keys.length === 1 ? 'clave' : 'claves'
                                }}
                            </UiButton>
                        </div>
                    </div>
                </article>
            </li>
        </ul>
    </main>
</template>
