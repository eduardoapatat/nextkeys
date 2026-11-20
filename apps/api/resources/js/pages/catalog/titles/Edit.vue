<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TitleController from '@/actions/App/Http/Controllers/Catalog/TitleController';
import TitleForm from '@/components/catalog/TitleForm.vue';
import type { TitleFormValues } from '@/types';
import Heading from '@/components/Heading.vue';

defineProps<{
    title: TitleFormValues;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Títulos', href: TitleController.index() }],
    },
});
</script>

<template>
    <Head title="Editar título" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading title="Editar título" />
        </div>

        <TitleForm :title="title" />
    </div>
</template>
