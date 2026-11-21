<script setup lang="ts">
const link =
    'flex h-11 shrink-0 items-center px-3 font-display font-bold tracking-wide whitespace-nowrap uppercase text-chalk-muted transition-colors duration-150 hover:text-chalk';
</script>

<template>
    <nav aria-label="Principal" class="border-t border-line bg-ink-2">
        <ul
            class="mx-auto flex max-w-7xl items-center overflow-x-auto px-2 [scrollbar-width:none]"
        >
            <li>
                <NuxtLink :to="catalogLink()" :class="[link, 'text-chalk']">
                    Catálogo
                </NuxtLink>
            </li>
            <li aria-hidden="true" class="mx-1 h-4 w-px shrink-0 bg-line" />
            <li v-for="family in platformFamilies" :key="family.label">
                <NuxtLink
                    :to="catalogLink({ platform: [...family.platform] })"
                    :class="link"
                >
                    {{ family.label }}
                </NuxtLink>
            </li>
            <li aria-hidden="true" class="mx-1 h-4 w-px shrink-0 bg-line" />
            <li v-for="item in productTypeLinks" :key="item.label">
                <NuxtLink
                    :to="catalogLink({ type: [...item.type] })"
                    :class="link"
                >
                    {{ item.label }}
                </NuxtLink>
            </li>
            <li class="ml-auto">
                <a href="#" :class="link">Blog</a>
            </li>
        </ul>
    </nav>
</template>
