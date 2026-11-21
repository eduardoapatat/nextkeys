<script setup lang="ts">
/**
 * The small-screen menu: a native modal <dialog> sliding in from the left
 * (focus trap, Escape and the backdrop come from the platform). It closes on
 * navigation.
 */
const dialog = ref<HTMLDialogElement | null>(null);
const customer = useCustomer();
const route = useRoute();

function open() {
    dialog.value?.showModal();
}

function close() {
    dialog.value?.close();
}

watch(() => route.fullPath, close);

defineExpose({ open });

const link =
    'flex h-11 items-center rounded-control px-3 text-chalk-muted hover:bg-panel hover:text-chalk';
</script>

<template>
    <dialog
        ref="dialog"
        aria-label="Menú"
        class="mobile-menu m-0 h-dvh max-h-dvh w-[min(22rem,88vw)] max-w-none border-r border-line bg-ink p-0 text-chalk backdrop:bg-ink/70"
        @click.self="close"
    >
        <div class="flex h-full flex-col gap-6 overflow-y-auto p-4">
            <div class="flex items-center justify-between">
                <SiteLogo />
                <button
                    type="button"
                    class="pressable grid size-10 place-items-center rounded-control text-chalk-muted hover:text-chalk"
                    aria-label="Cerrar menú"
                    @click="close"
                >
                    <UiIcon name="close" :size="22" />
                </button>
            </div>

            <nav aria-label="Menú móvil" class="flex flex-col gap-6">
                <ul>
                    <li>
                        <NuxtLink :to="catalogLink()" :class="link"
                            >Catálogo</NuxtLink
                        >
                    </li>
                    <li v-if="customer">
                        <NuxtLink to="/biblioteca" :class="link"
                            >Mi biblioteca</NuxtLink
                        >
                    </li>
                    <li v-if="customer">
                        <NuxtLink to="/pedidos" :class="link"
                            >Mis pedidos</NuxtLink
                        >
                    </li>
                    <li>
                        <NuxtLink
                            :to="customer ? '/cuenta' : '/cuenta/ingresar'"
                            :class="link"
                            >{{ customer ? 'Mi cuenta' : 'Ingresar' }}</NuxtLink
                        >
                    </li>
                </ul>
                <div>
                    <p
                        class="px-3 pb-1 font-display text-sm font-extrabold tracking-wider text-chalk-faint uppercase"
                    >
                        Plataformas
                    </p>
                    <ul>
                        <li
                            v-for="family in platformFamilies"
                            :key="family.label"
                        >
                            <NuxtLink
                                :to="
                                    catalogLink({
                                        platform: [...family.platform],
                                    })
                                "
                                :class="link"
                                >{{ family.label }}</NuxtLink
                            >
                        </li>
                    </ul>
                </div>
                <div>
                    <p
                        class="px-3 pb-1 font-display text-sm font-extrabold tracking-wider text-chalk-faint uppercase"
                    >
                        Tipos
                    </p>
                    <ul>
                        <li v-for="item in productTypeLinks" :key="item.label">
                            <NuxtLink
                                :to="catalogLink({ type: [...item.type] })"
                                :class="link"
                                >{{ item.label }}</NuxtLink
                            >
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </dialog>
</template>
