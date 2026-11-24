<script setup lang="ts">
import type { Shipment } from '~/api/schemas';

/** Where the parcel of a paid order is, and where it goes. */
const props = defineProps<{ shipment: Shipment }>();

const steps = computed(() => {
    const reached = ['pending', 'shipped', 'delivered'].indexOf(
        props.shipment.status.slug,
    );

    return [
        { label: 'Por despachar', date: null },
        { label: 'Despachado', date: props.shipment.shipped_at },
        { label: 'Entregado', date: props.shipment.delivered_at },
    ].map((step, index) => ({
        ...step,
        done: index <= reached,
        current: index === reached,
    }));
});
</script>

<template>
    <section
        aria-labelledby="shipment-heading"
        class="flex flex-col gap-5 rounded-card border border-line bg-bg-subtle p-5"
    >
        <h2 id="shipment-heading" class="text-2xl text-fg uppercase">Envío</h2>

        <ol class="grid grid-cols-3 gap-2" aria-label="Estado del envío">
            <li
                v-for="step in steps"
                :key="step.label"
                class="flex flex-col gap-1 border-t-4 pt-2"
                :class="step.done ? 'border-brand' : 'border-line'"
                :aria-current="step.current ? 'step' : undefined"
            >
                <span
                    class="font-display text-sm font-extrabold tracking-wide uppercase"
                    :class="step.done ? 'text-fg' : 'text-fg-faint'"
                    >{{ step.label }}</span
                >
                <time
                    v-if="step.date"
                    :datetime="step.date"
                    class="tabular text-xs text-fg-muted"
                    >{{ formatDateTime(step.date) }}</time
                >
            </li>
        </ol>

        <p v-if="shipment.carrier && shipment.tracking_number" class="text-fg">
            Va con <strong>{{ shipment.carrier.name }}</strong>, número de
            seguimiento
            <a
                v-if="shipment.tracking_url"
                :href="shipment.tracking_url"
                target="_blank"
                rel="noopener noreferrer"
                class="font-mono text-brand underline hover:text-fg"
                >{{ shipment.tracking_number }}</a><span
                v-else
                class="font-mono"
                >{{ shipment.tracking_number }}</span
            >.
        </p>
        <p v-else class="text-fg-muted">
            Estamos preparando tu paquete. Te avisamos por correo cuando salga.
        </p>

        <address class="text-fg-muted not-italic">
            <strong class="block text-fg">{{
                shipment.address.recipient_name
            }}</strong>
            {{ shipment.address.street }} {{ shipment.address.street_number
            }}<template v-if="shipment.address.apartment"
                >, {{ shipment.address.apartment }}</template
            ><br />
            {{ shipment.address.commune }}, {{ shipment.address.region.name }}
        </address>

        <p v-if="shipment.returnable_until" class="text-fg-muted">
            Puedes ejercer tu derecho a retracto hasta el
            <time :datetime="shipment.returnable_until" class="text-fg">{{
                formatDate(shipment.returnable_until)
            }}</time>.
        </p>
    </section>
</template>
