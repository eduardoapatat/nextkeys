<script setup lang="ts">
import { logout, resendVerification } from '~/api/auth';

definePageMeta({ middleware: 'auth' });
useHead({ title: 'Mi cuenta' });

const customer = useCustomer();
const errors = ref<string[]>([]);
const notice = ref('');
const pending = ref(false);

async function resend() {
    pending.value = true;
    errors.value = [];
    notice.value = '';

    try {
        await resendVerification();
        notice.value = 'Te enviamos un nuevo enlace de verificación.';
    } catch (error) {
        errors.value = formErrorMessages(error);
    } finally {
        pending.value = false;
    }
}

async function signOut() {
    pending.value = true;
    errors.value = [];

    try {
        await logout();
        customer.value = null;
        await navigateTo('/');
    } catch (error) {
        errors.value = formErrorMessages(error);
    } finally {
        pending.value = false;
    }
}
</script>

<template>
    <main v-if="customer" class="account-page">
        <h1>Mi cuenta</h1>

        <FormErrors :messages="errors" />
        <p v-if="notice" role="status">{{ notice }}</p>

        <dl
            class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 rounded-card border border-line bg-bg-subtle p-6"
        >
            <dt class="text-fg-faint">Nombre</dt>
            <dd class="text-fg">{{ customer.name }}</dd>
            <dt class="text-fg-faint">Correo electrónico</dt>
            <dd class="break-all text-fg">{{ customer.email }}</dd>
        </dl>

        <section
            v-if="!customer.email_verified"
            aria-labelledby="verify-heading"
        >
            <h2 id="verify-heading">Verifica tu correo</h2>
            <p class="text-fg-muted">
                Para pagar necesitas verificar tu correo: ahí te enviamos las
                claves. Revisa el enlace que mandamos a {{ customer.email }}.
            </p>
            <UiButton
                variant="primary"
                class="self-start"
                :disabled="pending"
                @click="resend"
            >
                Reenviar el correo
            </UiButton>
        </section>

        <nav aria-label="Tu actividad" class="grid grid-cols-2 gap-3">
            <UiButton to="/pedidos" size="lg"
                ><UiIcon name="receipt" />Mis pedidos</UiButton
            >
            <UiButton to="/biblioteca" size="lg"
                ><UiIcon name="library" />Mi biblioteca</UiButton
            >
            <UiButton to="/cuenta/direcciones" size="lg" class="col-span-2"
                ><UiIcon name="home" />Mis direcciones</UiButton
            >
        </nav>

        <UiButton
            variant="ghost"
            class="self-start"
            :disabled="pending"
            @click="signOut"
        >
            Cerrar sesión
        </UiButton>
    </main>
</template>
