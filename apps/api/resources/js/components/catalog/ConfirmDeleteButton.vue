<script setup lang="ts">
import type { RouteDefinition } from '@/wayfinder';
import { router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = defineProps<{
    /** The destroy route of the record. */
    action: RouteDefinition<'delete'>;
    /** What is deleted, shown in the dialog title ("el producto starfall-pc"). */
    subject: string;
}>();

const open = ref(false);
const processing = ref(false);

/**
 * The Action refuses records still in use with a validation error; show it
 * as a toast, since the table has no form to attach it to.
 */
function destroy(): void {
    router.delete(props.action.url, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onSuccess: () => (open.value = false),
        onError: (errors) => {
            open.value = false;
            toast.error(Object.values(errors)[0] ?? 'No se pudo eliminar.');
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                :aria-label="`Eliminar ${subject}`"
            >
                <Trash2 class="size-4" />
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>¿Eliminar {{ subject }}?</DialogTitle>
                <DialogDescription>
                    Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancelar</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="destroy"
                >
                    Eliminar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
