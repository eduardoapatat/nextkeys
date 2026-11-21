<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

/**
 * A game case at 3:4: the frame and band of the product's platform (official
 * marks, see `platformBox`) around the front. The front is the uploaded cover
 * when there is one; otherwise it prints the title, type and region on a
 * tint derived from the slug.
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
const platformNames = computed(() =>
    props.product.platforms.map((platform) => platform.name).join(', '),
);
const box = computed(() => platformBox(props.product.platforms));

const tone: Record<BoxTone, string> = {
    ps5: 'bg-box-ps5 text-ink',
    ps4: 'bg-box-ps4 text-chalk',
    xbox: 'bg-box-xbox text-chalk',
    nintendo: 'bg-box-nintendo text-chalk',
    pc: 'bg-box-pc text-chalk',
    multi: 'bg-box-multi text-chalk',
};

const frame = {
    sm: { case: 'px-0.75 pb-0.75', band: 'h-4.5 text-[0.55rem] px-0.5' },
    md: { case: 'px-1.25 pb-1.25', band: 'h-6 text-[0.7rem] px-1' },
    lg: { case: 'px-1.75 pb-1.75', band: 'h-8 text-sm px-1.5' },
};
</script>

<template>
    <div
        class="relative flex aspect-3/4 w-full flex-col overflow-hidden rounded-card"
        :class="[tone[box.tone], frame[size].case]"
        data-cover-box
        :data-tone="box.tone"
    >
        <p
            class="flex shrink-0 items-center gap-[0.6em] overflow-hidden py-[0.35em] font-display leading-none font-extrabold tracking-wide whitespace-nowrap uppercase"
            :class="frame[size].band"
            :aria-label="`Plataformas: ${platformNames}`"
        >
            <template v-for="(item, index) in box.items" :key="index">
                <span
                    v-if="index > 0"
                    aria-hidden="true"
                    class="h-[70%] w-px shrink-0 bg-current opacity-40"
                />
                <span
                    v-if="item.kind === 'mark'"
                    aria-hidden="true"
                    class="flex h-[70%] shrink-0 items-center gap-[0.4em]"
                >
                    <PlatformMark :name="item.mark" />
                    <span v-if="item.suffix">{{ item.suffix }}</span>
                </span>
                <span v-else aria-hidden="true">{{ item.text }}</span>
            </template>
        </p>

        <div class="relative min-h-0 flex-1 overflow-hidden rounded-[2px]">
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
                    class="mt-auto px-[8%] font-display leading-[0.92] font-extrabold tracking-tight text-balance uppercase"
                    :class="
                        {
                            sm: 'text-base',
                            md: 'text-[clamp(1.3rem,2.5vw,1.9rem)]',
                            lg: 'text-[clamp(1.9rem,4.8vw,3.3rem)]',
                        }[size]
                    "
                >
                    {{ product.title.name }}
                </p>
                <p
                    aria-hidden="true"
                    :class="{ 'max-sm:hidden': size === 'sm' }"
                    class="flex flex-wrap items-center gap-x-1.5 px-[8%] pt-[5%] pb-[7%] text-[0.65rem] font-medium tracking-wide text-chalk/90 uppercase"
                >
                    <span>{{ product.type.name }}</span>
                    <span v-if="product.region"
                        >· {{ product.region.name }}</span
                    >
                </p>
            </div>
        </div>
    </div>
</template>
