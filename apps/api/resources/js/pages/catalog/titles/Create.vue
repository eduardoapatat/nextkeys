<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TitleController from '@/actions/App/Http/Controllers/Catalog/TitleController';
import TitleForm from '@/components/catalog/TitleForm.vue';
import Heading from '@/components/Heading.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Títulos', href: TitleController.index() },
            { title: 'Nuevo', href: TitleController.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo título" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading title="Nuevo título" />
        </div>

        <TitleForm />
    </div>
</template>
