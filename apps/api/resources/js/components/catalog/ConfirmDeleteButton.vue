<script setup lang="ts">
import type { RouteDefinition } from '@/wayfinder';
import { Trash2 } from '@lucide/vue';
import ConfirmActionButton from '@/components/ConfirmActionButton.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    /** The destroy route of the record. */
    action: RouteDefinition<'delete'>;
    /** What is deleted, shown in the dialog title ("el producto starfall-pc"). */
    subject: string;
}>();
</script>

<template>
    <ConfirmActionButton
        :action="action"
        :title="`¿Eliminar ${subject}?`"
        description="Esta acción no se puede deshacer."
        confirm-label="Eliminar"
        confirm-variant="destructive"
        error-message="No se pudo eliminar."
    >
        <Button variant="ghost" size="icon" :aria-label="`Eliminar ${subject}`">
            <Trash2 class="size-4" />
        </Button>
    </ConfirmActionButton>
</template>
