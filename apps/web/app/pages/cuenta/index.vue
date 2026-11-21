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
    <main v-if="customer">
        <h1>Mi cuenta</h1>

        <FormErrors :messages="errors" />
        <p v-if="notice" role="status">{{ notice }}</p>

        <dl>
            <dt>Nombre</dt>
            <dd>{{ customer.name }}</dd>
            <dt>Correo electrónico</dt>
            <dd>{{ customer.email }}</dd>
        </dl>

        <section
            v-if="!customer.email_verified"
            aria-labelledby="verify-heading"
        >
            <h2 id="verify-heading">Verifica tu correo</h2>
            <p>
                Para pagar necesitas verificar tu correo: ahí te enviamos las
                claves. Revisa el enlace que mandamos a {{ customer.email }}.
            </p>
            <button type="button" :disabled="pending" @click="resend">
                Reenviar el correo
            </button>
        </section>

        <p>
            <button type="button" :disabled="pending" @click="signOut">
                Cerrar sesión
            </button>
        </p>
    </main>
</template>
