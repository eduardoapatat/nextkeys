<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Catalog/ProductController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
import type {
    ProductFormOptions,
    ProductFormValues,
    ProductTypeValue,
} from '@/types';

/**
 * Create and edit form of a product. Its fields follow the type: key types
 * need a service and a region and cover several platforms; physical
 * products have stock and exactly one platform. The type is chosen once.
 */
const props = defineProps<{
    options: ProductFormOptions;
    /** The product being edited; absent on creation. */
    product?: ProductFormValues;
}>();

const form = useForm({
    type: props.product?.type ?? ('digital_key' as ProductTypeValue),
    title_id: props.product?.title_id ?? null,
    activation_service_id: props.product?.activation_service_id ?? null,
    region_id: props.product?.region_id ?? null,
    platform_ids: props.product?.platform_ids ?? ([] as number[]),
    price: props.product?.price ?? null,
    stock: props.product?.stock ?? null,
    is_published: props.product?.is_published ?? false,
    slug: props.product?.slug ?? '',
});

/**
 * A duplicate identity or a database rule is reported under `product`, a key
 * that is not a field of the form.
 */
const identityError = computed(
    () => (form.errors as Partial<Record<string, string>>).product,
);

const isPhysical = computed(() => form.type === 'physical');
const typeLabel = computed(
    () => props.options.types.find((type) => type.value === form.type)?.label,
);

/** Physical products cover one platform, picked from a single select. */
const physicalPlatform = computed<number | null>({
    get: () => form.platform_ids[0] ?? null,
    set: (id) => (form.platform_ids = id === null ? [] : [id]),
});

watch(isPhysical, (physical) => {
    if (physical) {
        form.platform_ids = form.platform_ids.slice(0, 1);
    } else {
        form.stock = null;
    }
});

function togglePlatform(id: number, checked: boolean): void {
    form.platform_ids = checked
        ? [...form.platform_ids, id]
        : form.platform_ids.filter((platformId) => platformId !== id);
}

/** Empty selects and inputs send an empty string; the server expects null. */
function nullableNumber(value: unknown): number | null {
    return value === '' || value === null ? null : Number(value);
}

function submit(): void {
    if (props.product) {
        form.submit(ProductController.update(props.product.id));
    } else {
        form.submit(ProductController.store());
    }
}
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <div
            v-if="identityError"
            role="alert"
            class="rounded-md border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive"
        >
            {{ identityError }}
        </div>

        <div class="grid gap-2">
            <Label for="type">Tipo</Label>
            <NativeSelect
                v-if="!product"
                id="type"
                v-model="form.type"
                class="w-full"
            >
                <NativeSelectOption
                    v-for="type in options.types"
                    :key="type.value"
                    :value="type.value"
                >
                    {{ type.label }}
                </NativeSelectOption>
            </NativeSelect>
            <div v-else>
                <Badge variant="secondary">{{ typeLabel }}</Badge>
                <p class="mt-1 text-sm text-muted-foreground">
                    El tipo no se puede cambiar.
                </p>
            </div>
            <InputError :message="form.errors.type" />
        </div>

        <div class="grid gap-2">
            <Label for="title_id">Título</Label>
            <NativeSelect
                id="title_id"
                :model-value="form.title_id ?? ''"
                class="w-full"
                @update:model-value="form.title_id = nullableNumber($event)"
            >
                <NativeSelectOption value="" disabled>
                    Elige un título
                </NativeSelectOption>
                <NativeSelectOption
                    v-for="title in options.titles"
                    :key="title.value"
                    :value="title.value"
                >
                    {{ title.label }}
                </NativeSelectOption>
            </NativeSelect>
            <InputError :message="form.errors.title_id" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="activation_service_id">
                    Servicio de activación
                    <span v-if="isPhysical" class="text-muted-foreground">
                        (opcional)
                    </span>
                </Label>
                <NativeSelect
                    id="activation_service_id"
                    :model-value="form.activation_service_id ?? ''"
                    class="w-full"
                    @update:model-value="
                        form.activation_service_id = nullableNumber($event)
                    "
                >
                    <NativeSelectOption value="" :disabled="!isPhysical">
                        {{
                            isPhysical
                                ? 'Ninguno (disco o cartucho)'
                                : 'Elige un servicio'
                        }}
                    </NativeSelectOption>
                    <NativeSelectOption
                        v-for="service in options.activationServices"
                        :key="service.value"
                        :value="service.value"
                    >
                        {{ service.label }}
                    </NativeSelectOption>
                </NativeSelect>
                <InputError :message="form.errors.activation_service_id" />
            </div>

            <div class="grid gap-2">
                <Label for="region_id">
                    Región
                    <span v-if="isPhysical" class="text-muted-foreground">
                        (opcional)
                    </span>
                </Label>
                <NativeSelect
                    id="region_id"
                    :model-value="form.region_id ?? ''"
                    class="w-full"
                    @update:model-value="
                        form.region_id = nullableNumber($event)
                    "
                >
                    <NativeSelectOption value="" :disabled="!isPhysical">
                        {{ isPhysical ? 'Ninguna' : 'Elige una región' }}
                    </NativeSelectOption>
                    <NativeSelectOption
                        v-for="region in options.regions"
                        :key="region.value"
                        :value="region.value"
                    >
                        {{ region.label }}
                    </NativeSelectOption>
                </NativeSelect>
                <InputError :message="form.errors.region_id" />
            </div>
        </div>

        <div class="grid gap-2">
            <template v-if="isPhysical">
                <Label for="platform">Plataforma</Label>
                <NativeSelect
                    id="platform"
                    :model-value="physicalPlatform ?? ''"
                    class="w-full"
                    @update:model-value="
                        physicalPlatform = nullableNumber($event)
                    "
                >
                    <NativeSelectOption value="" disabled>
                        Elige una plataforma
                    </NativeSelectOption>
                    <NativeSelectOption
                        v-for="platform in options.platforms"
                        :key="platform.value"
                        :value="platform.value"
                    >
                        {{ platform.label }}
                    </NativeSelectOption>
                </NativeSelect>
            </template>
            <fieldset v-else class="grid gap-2">
                <legend class="mb-2 text-sm font-medium">Plataformas</legend>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    <Label
                        v-for="platform in options.platforms"
                        :key="platform.value"
                        class="flex items-center gap-2 font-normal"
                    >
                        <Checkbox
                            :model-value="
                                form.platform_ids.includes(platform.value)
                            "
                            @update:model-value="
                                togglePlatform(platform.value, $event === true)
                            "
                        />
                        {{ platform.label }}
                    </Label>
                </div>
            </fieldset>
            <InputError
                :message="
                    form.errors.platform_ids ?? form.errors['platform_ids.0']
                "
            />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="price">Precio (CLP)</Label>
                <Input
                    id="price"
                    :model-value="form.price ?? ''"
                    @update:model-value="form.price = nullableNumber($event)"
                    type="number"
                    min="1"
                    step="1"
                    placeholder="19990"
                />
                <InputError :message="form.errors.price" />
            </div>

            <div v-if="isPhysical" class="grid gap-2">
                <Label for="stock">Stock</Label>
                <Input
                    id="stock"
                    :model-value="form.stock ?? ''"
                    @update:model-value="form.stock = nullableNumber($event)"
                    type="number"
                    min="0"
                    step="1"
                />
                <InputError :message="form.errors.stock" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="slug">Slug</Label>
            <Input
                id="slug"
                v-model="form.slug"
                placeholder="starfall-steam-windows-global"
            />
            <p class="text-sm text-muted-foreground">
                {{
                    product
                        ? 'Déjalo vacío para conservar el actual.'
                        : 'Déjalo vacío para generarlo a partir del título, el servicio, las plataformas y la región.'
                }}
            </p>
            <InputError :message="form.errors.slug" />
        </div>

        <Label class="flex items-center gap-2 font-normal">
            <Checkbox
                :model-value="form.is_published"
                @update:model-value="form.is_published = $event === true"
            />
            Publicado (visible en la tienda)
        </Label>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ product ? 'Guardar cambios' : 'Crear producto' }}
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="ProductController.index()">Cancelar</Link>
            </Button>
        </div>
    </form>
</template>
