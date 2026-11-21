<script setup lang="ts">
import type { CatalogFilters } from '~/api/schemas';

/**
 * The full-width turquoise band: the platform families and the product types,
 * each a real catalog filter (the home has no side menu). The platform names under each family come from the API.
 */
const props = defineProps<{ filters: CatalogFilters }>();

function platformNames(slugs: readonly string[]): string {
    return props.filters.platforms
        .filter((platform) => slugs.includes(platform.slug))
        .map((platform) => platform.name)
        .join(' · ');
}

const typeNotes: Record<string, string> = {
    digital_key: 'Llega a tu correo y a tu biblioteca',
    license: 'Programas con su clave de activación',
    physical: 'La caja, para tu estante',
};
</script>

<template>
    <section
        aria-labelledby="categories-heading"
        class="surface-brand py-12 md:py-16"
    >
        <div class="mx-auto max-w-7xl px-4">
            <h2
                id="categories-heading"
                class="text-4xl text-fg uppercase md:text-5xl"
            >
                Elige tu plataforma
            </h2>
            <ul class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4 md:gap-4">
                <li v-for="family in platformFamilies" :key="family.label">
                    <NuxtLink
                        :to="catalogLink({ platform: [...family.platform] })"
                        class="pressable group flex h-full min-h-36 flex-col justify-between gap-6 rounded-card border border-line bg-surface p-4 hover:border-accent hover:bg-accent md:p-5"
                    >
                        <UiIcon
                            name="arrowRight"
                            class="self-end text-fg-muted transition-transform duration-200 group-hover:translate-x-1 group-hover:text-accent-fg"
                        />
                        <span>
                            <span
                                class="block font-display text-3xl leading-none font-extrabold text-fg uppercase group-hover:text-accent-fg md:text-4xl"
                                >{{ family.label }}</span
                            >
                            <span
                                class="mt-1 block text-sm text-fg-muted group-hover:text-accent-fg"
                                >{{ platformNames(family.platform) }}</span
                            >
                        </span>
                    </NuxtLink>
                </li>
            </ul>
            <ul class="mt-4 grid gap-3 sm:grid-cols-3 md:gap-4">
                <li v-for="item in productTypeLinks" :key="item.label">
                    <NuxtLink
                        :to="catalogLink({ type: [...item.type] })"
                        class="pressable group flex items-center justify-between gap-4 rounded-card border border-line-strong px-4 py-3 hover:bg-surface-2"
                    >
                        <span>
                            <span
                                class="block font-display text-xl font-extrabold text-fg uppercase"
                                >{{ item.label }}</span
                            >
                            <span class="block text-sm text-fg-muted">{{
                                typeNotes[item.type[0]]
                            }}</span>
                        </span>
                        <UiIcon
                            name="arrowRight"
                            class="text-fg transition-transform duration-200 group-hover:translate-x-1"
                        />
                    </NuxtLink>
                </li>
            </ul>
        </div>
    </section>
</template>
