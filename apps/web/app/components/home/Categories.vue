<script setup lang="ts">
import type { CatalogFilters } from '~/api/schemas';

/**
 * Category blocks: the platform families and the product types, each a real
 * catalog filter. The platform names under each family come from the API.
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
    <section aria-labelledby="categories-heading">
        <h2 id="categories-heading" class="text-3xl text-chalk uppercase">
            Elige tu plataforma
        </h2>
        <ul class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <li v-for="family in platformFamilies" :key="family.label">
                <NuxtLink
                    :to="catalogLink({ platform: [...family.platform] })"
                    class="pressable group flex h-full flex-col justify-end gap-6 pt-8 rounded-card border border-line bg-panel p-4 hover:border-crt"
                >
                    <span>
                        <span
                            class="block font-display text-3xl leading-none font-bold text-chalk uppercase group-hover:text-crt md:text-4xl"
                            >{{ family.label }}</span
                        >
                        <span class="mt-1 block text-sm text-chalk-muted">{{
                            platformNames(family.platform)
                        }}</span>
                    </span>
                </NuxtLink>
            </li>
        </ul>
        <ul class="mt-3 grid gap-3 sm:grid-cols-3">
            <li v-for="item in productTypeLinks" :key="item.label">
                <NuxtLink
                    :to="catalogLink({ type: [...item.type] })"
                    class="pressable group flex items-center justify-between gap-4 rounded-card border border-line px-4 py-3 hover:border-sticker"
                >
                    <span>
                        <span
                            class="block font-display text-xl font-bold text-chalk uppercase"
                            >{{ item.label }}</span
                        >
                        <span class="block text-sm text-chalk-muted">{{
                            typeNotes[item.type[0]]
                        }}</span>
                    </span>
                    <UiIcon
                        name="arrowRight"
                        class="text-chalk-faint transition-transform duration-200 group-hover:translate-x-1 group-hover:text-sticker"
                    />
                </NuxtLink>
            </li>
        </ul>
    </section>
</template>
