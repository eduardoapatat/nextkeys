<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import TitleController from '@/actions/App/Http/Controllers/Catalog/TitleController';
import TitlesTable from '@/components/data-tables/TitlesTable.vue';
import type { Title } from '@/components/data-tables/TitlesTable.vue';
import type { ServerDataTable } from '@/lib/data-table-server';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    titles: ServerDataTable<Title>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Títulos', href: TitleController.index() }],
    },
});
</script>

<template>
    <Head title="Títulos" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Títulos"
                description="La obra (un juego o un software) con el contenido que comparten sus productos."
            />
            <Button as-child>
                <Link :href="TitleController.create()">Nuevo título</Link>
            </Button>
        </div>

        <TitlesTable :titles="titles" />
    </div>
</template>
