<script setup lang="ts">
defineProps<{ hasPhysicalItems: boolean }>();
</script>

<!--
    Shown before payment, as prominent as the price (Ley 19.496 art. 3 bis
    and Decreto 52): see docs/domain/payments.md, "Chile". It uses the same
    size as the total it sits next to.
-->
<template>
    <p class="withdrawal-notice font-sans text-base leading-relaxed">
        <strong>Derecho a retracto:</strong> las claves digitales entregadas no
        admiten derecho a retracto (exclusión informada antes del pago, art. 3
        bis letra b) de la Ley 19.496 y Decreto 52 de 2024). Mantienes la
        garantía legal si una clave no funciona.
        <template v-if="hasPhysicalItems">
            Los productos físicos tienen 10 días de derecho a retracto desde que
            los recibes.
        </template>
    </p>
</template>
