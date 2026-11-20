<script setup lang="ts">
import type { RouteDefinition } from '@/wayfinder';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
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

const props = withDefaults(
    defineProps<{
        /** The route the confirmation sends (POST or DELETE). */
        action: RouteDefinition<'post'> | RouteDefinition<'delete'>;
        title: string;
        description: string;
        /** Text of the confirm button. */
        confirmLabel: string;
        confirmVariant?: ButtonVariants['variant'];
        /** Toast shown when the request fails without a message. */
        errorMessage?: string;
    }>(),
    {
        confirmVariant: 'default',
        errorMessage: 'No se pudo completar la acción.',
    },
);

const open = ref(false);
const processing = ref(false);

/**
 * Actions refuse with a validation error; show it as a toast, since there is
 * no form to attach it to.
 */
function confirm(): void {
    router.visit(props.action.url, {
        method: props.action.method,
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onSuccess: () => (open.value = false),
        onError: (errors) => {
            open.value = false;
            toast.error(Object.values(errors)[0] ?? props.errorMessage);
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Volver</Button>
                </DialogClose>
                <Button
                    :variant="confirmVariant"
                    :disabled="processing"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
