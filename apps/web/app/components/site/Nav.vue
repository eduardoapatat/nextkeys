<script setup lang="ts">
/**
 * The black menu bar under the header, after Koparion's main menu: "Inicio",
 * the catalog, the platform families and the product types. The current
 * page (path and query) is filled in yellow.
 */
const route = useRoute();
const router = useRouter();

const items = computed(() => [
    { label: 'Inicio', to: '/' },
    { label: 'Catálogo', to: catalogLink() },
    ...platformFamilies.map((family) => ({
        label: family.label,
        to: catalogLink({ platform: [...family.platform] }),
    })),
    ...productTypeLinks.map((item) => ({
        label: item.label,
        to: catalogLink({ type: [...item.type] }),
    })),
]);

function isCurrent(to: Parameters<typeof router.resolve>[0]): boolean {
    return router.resolve(to).fullPath === route.fullPath;
}
</script>

<template>
    <nav aria-label="Principal" class="bg-bar">
        <ul
            class="mx-auto flex max-w-7xl overflow-x-auto px-4 [scrollbar-width:none]"
        >
            <li v-for="item in items" :key="item.label" class="shrink-0">
                <NuxtLink
                    :to="item.to"
                    class="menu-tab"
                    :aria-current="isCurrent(item.to) ? 'page' : undefined"
                >
                    {{ item.label }}
                </NuxtLink>
            </li>
        </ul>
    </nav>
</template>
