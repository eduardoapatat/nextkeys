<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import OrderController from '@/actions/App/Http/Controllers/Orders/OrderController';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';

/**
 * Marks a paid order as shipped with its courier and tracking number; the
 * customer then gets the shipping email.
 */
defineProps<{
    reference: string;
    carriers: { value: string; label: string }[];
}>();

const open = ref(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button>Marcar como despachado</Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="OrderController.ship.form(reference)"
                :options="{ preserveScroll: true }"
                class="grid gap-4"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <DialogHeader>
                    <DialogTitle>Despachar {{ reference }}</DialogTitle>
                    <DialogDescription>
                        Le enviamos al cliente un email con la empresa y el
                        enlace de seguimiento.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="carrier">Empresa de envío</Label>
                    <NativeSelect id="carrier" name="carrier" required>
                        <NativeSelectOption
                            v-for="carrier in carriers"
                            :key="carrier.value"
                            :value="carrier.value"
                        >
                            {{ carrier.label }}
                        </NativeSelectOption>
                    </NativeSelect>
                    <InputError :message="errors.carrier" />
                </div>

                <div class="grid gap-2">
                    <Label for="tracking_number">Número de seguimiento</Label>
                    <Input
                        id="tracking_number"
                        name="tracking_number"
                        required
                        maxlength="40"
                        autocomplete="off"
                    />
                    <InputError :message="errors.tracking_number" />
                </div>

                <InputError :message="errors.order" />

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary"
                            >Volver</Button
                        >
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        Despachar
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
