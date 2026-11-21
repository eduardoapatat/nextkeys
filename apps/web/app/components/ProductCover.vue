<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

/**
 * A game-case front at 3:4. With `cover_url` it shows the uploaded cover;
 * without one it prints a cover from the product's own data: the platform
 * band on top, the title, and the type and region at the foot.
 */
const props = withDefaults(
    defineProps<{
        product: Pick<
            ProductSummary,
            'slug' | 'cover_url' | 'platforms' | 'type' | 'region'
        > & { title: { name: string } };
        size?: 'sm' | 'md' | 'lg';
        eager?: boolean;
    }>(),
    { size: 'md', eager: false },
);

const label = computed(() => `Carátula de ${props.product.title.name}`);
const platforms = computed(() =>
    props.product.platforms.map((platform) => platform.name).join(' · '),
);
</script>

<template>
    <div
        class="relative aspect-3/4 w-full overflow-hidden rounded-card bg-panel-2"
    >
        <img
            v-if="product.cover_url"
            :src="product.cover_url"
            :alt="label"
            :loading="eager ? 'eager' : 'lazy'"
            class="size-full object-cover"
        />
        <div
            v-else
            role="img"
            :aria-label="label"
            data-cover="generated"
            class="flex size-full flex-col text-chalk"
            :class="coverTint(product.slug)"
        >
            <p
                aria-hidden="true"
                class="truncate bg-ink/55 px-[8%] py-[3.5%] font-display font-bold tracking-wider uppercase"
                :class="size === 'sm' ? 'text-[0.6rem]' : 'text-xs'"
            >
                {{ platforms }}
            </p>
            <p
                aria-hidden="true"
                class="mt-auto px-[8%] font-display leading-[0.92] font-bold tracking-tight text-balance uppercase"
                :class="
                    {
                        sm: 'text-lg',
                        md: 'text-[clamp(1.35rem,2.6vw,2rem)]',
                        lg: 'text-[clamp(2rem,5vw,3.5rem)]',
                    }[size]
                "
            >
                {{ product.title.name }}
            </p>
            <p
                aria-hidden="true"
                class="flex items-center justify-between gap-2 px-[8%] pt-[5%] pb-[7%] text-[0.65rem] font-medium tracking-wide text-chalk/75 uppercase"
            >
                <span>{{ product.type.name }}</span>
                <span v-if="product.region">{{ product.region.name }}</span>
            </p>
        </div>
    </div>
</template>
