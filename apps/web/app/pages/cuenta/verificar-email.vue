<script setup lang="ts">
import { fetchCustomer, verifyEmail } from '~/api/auth';
import type { VerificationLink } from '~/api/auth';

useHead({ title: 'Verificar correo' });

const route = useRoute();
const customer = useCustomer();
const status = ref<'pending' | 'verified' | 'invalid'>('pending');

function linkFromQuery(): VerificationLink | null {
    const { id, hash, expires, signature } = route.query;

    return typeof id === 'string' &&
        typeof hash === 'string' &&
        typeof expires === 'string' &&
        typeof signature === 'string'
        ? { id, hash, expires, signature }
        : null;
}

// A POST with the CSRF cookie: only in the browser, never during SSR.
onMounted(async () => {
    const link = linkFromQuery();

    if (!link) {
        status.value = 'invalid';
        return;
    }

    try {
        await verifyEmail(link);
        status.value = 'verified';

        if (customer.value) {
            customer.value = await fetchCustomer();
        }
    } catch {
        status.value = 'invalid';
    }
});
</script>

<template>
    <main>
        <h1>Verificar correo</h1>

        <p v-if="status === 'pending'" role="status">Verificando…</p>
        <template v-else-if="status === 'verified'">
            <p role="status">Tu correo quedó verificado. Ya puedes pagar.</p>
            <p><NuxtLink to="/">Ir al catálogo</NuxtLink></p>
        </template>
        <template v-else>
            <p role="alert">
                El enlace no es válido o expiró. Desde tu cuenta puedes pedir
                uno nuevo.
            </p>
            <p><NuxtLink to="/cuenta">Ir a mi cuenta</NuxtLink></p>
        </template>
    </main>
</template>
