<script setup lang="ts">
import { Head, setLayoutProps, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Catalog/ProductController';
import ProductKeyController from '@/actions/App/Http/Controllers/Catalog/ProductKeyController';
import ProductKeysTable from '@/components/data-tables/ProductKeysTable.vue';
import type { ProductKeyRow } from '@/components/data-tables/ProductKeysTable.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ServerDataTable } from '@/lib/data-table-server';
import type { KeyImportResult, KeyProduct } from '@/types';

const props = defineProps<{
    product: KeyProduct;
    keys: ServerDataTable<ProductKeyRow>;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Productos', href: ProductController.index() },
        {
            title: props.product.slug,
            href: ProductController.edit(props.product.id),
        },
        { title: 'Keys', href: ProductKeyController.index(props.product.id) },
    ],
});

/** "8 disponibles · 1 reservada · 0 vendidas" */
const statusSummary = computed(() =>
    props.product.statusCounts
        .map(
            ({ label, total }) =>
                `${total} ${label.toLowerCase()}${total === 1 ? '' : 's'}`,
        )
        .join(' · '),
);

const form = useForm<{ file: File | null }>({ file: null });
const fileInput = ref<InstanceType<typeof Input> | null>(null);

/**
 * Flash data is gone after the next visit (sorting or paging the table), so
 * the last result is kept until another file is uploaded.
 */
const result = ref<KeyImportResult | null>(null);
const page = usePage();

watch(
    () => page.flash.keyImport,
    (keyImport) => {
        if (keyImport) {
            result.value = keyImport;
        }
    },
    { immediate: true },
);

function onFileChange(event: Event): void {
    form.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function submit(): void {
    result.value = null;
    form.submit(ProductKeyController.store(props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            const element = fileInput.value?.$el as
                | HTMLInputElement
                | undefined;

            if (element) {
                element.value = '';
            }
        },
    });
}
</script>

<template>
    <Head :title="`Keys de ${product.slug}`" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <Heading
            :title="`Keys de ${product.slug}`"
            :description="statusSummary"
        />

        <Card>
            <CardHeader>
                <CardTitle>Importar keys</CardTitle>
                <CardDescription>
                    Un archivo .csv o .txt de hasta 1 MB, con un código por
                    línea y sin encabezado (máximo 5.000). Cada código tiene de
                    5 a 100 caracteres entre letras, números y guiones. Las
                    líneas vacías se ignoran.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form
                    class="flex flex-col gap-3 sm:flex-row sm:items-end"
                    @submit.prevent="submit"
                >
                    <div class="grid flex-1 gap-2">
                        <Label for="file">Archivo</Label>
                        <Input
                            id="file"
                            ref="fileInput"
                            type="file"
                            accept=".csv,.txt,text/csv,text/plain"
                            @change="onFileChange"
                        />
                        <InputError :message="form.errors.file" />
                        <InputError
                            :message="
                                (form.errors as Partial<Record<string, string>>)
                                    .product
                            "
                        />
                    </div>
                    <Button
                        type="submit"
                        :disabled="!form.file || form.processing"
                    >
                        Importar
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Alert
            v-if="result"
            :variant="result.rejectedCount > 0 ? 'destructive' : 'default'"
        >
            <AlertTitle>
                {{ result.imported }} importadas, {{ result.rejectedCount }}
                rechazadas
            </AlertTitle>
            <AlertDescription v-if="result.rejectedCount > 0">
                <ul class="mt-1 list-none space-y-0.5 text-sm">
                    <li
                        v-for="rejection in result.rejections"
                        :key="rejection.line"
                    >
                        Línea {{ rejection.line }}: {{ rejection.reason }}
                    </li>
                </ul>
                <p
                    v-if="result.rejectedCount > result.rejections.length"
                    class="mt-1"
                >
                    y {{ result.rejectedCount - result.rejections.length }}
                    más.
                </p>
            </AlertDescription>
        </Alert>

        <ProductKeysTable :keys="keys" />
    </div>
</template>
