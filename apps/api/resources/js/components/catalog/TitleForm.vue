<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import TitleController from '@/actions/App/Http/Controllers/Catalog/TitleController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { TitleFormValues } from '@/types';

const props = defineProps<{
    /** The title being edited; absent on creation. */
    title?: TitleFormValues;
}>();

const form = useForm({
    name: props.title?.name ?? '',
    description: props.title?.description ?? '',
});

function submit(): void {
    if (props.title) {
        form.submit(TitleController.update(props.title.id));
    } else {
        form.submit(TitleController.store());
    }
}
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="name">Nombre</Label>
            <Input id="name" v-model="form.name" placeholder="Starfall" />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Descripción</Label>
            <Textarea id="description" v-model="form.description" rows="6" />
            <p class="text-sm text-muted-foreground">
                La comparten todos los productos del título.
            </p>
            <InputError :message="form.errors.description" />
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ title ? 'Guardar cambios' : 'Crear título' }}
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="TitleController.index()">Cancelar</Link>
            </Button>
        </div>
    </form>
</template>
