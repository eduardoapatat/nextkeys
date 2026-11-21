<script setup lang="ts">
import { forgotPassword } from '~/api/auth';

useHead({ title: 'Recuperar contraseña' });

const email = ref('');
const errors = ref<string[]>([]);
const notice = ref('');
const pending = ref(false);

async function submit() {
    pending.value = true;
    errors.value = [];
    notice.value = '';

    try {
        notice.value = await forgotPassword(email.value);
    } catch (error) {
        errors.value = formErrorMessages(error);
    } finally {
        pending.value = false;
    }
}
</script>

<template>
    <main class="account-page">
        <h1>Recuperar contraseña</h1>

        <p v-if="notice" role="status">{{ notice }}</p>
        <form v-else @submit.prevent="submit">
            <FormErrors :messages="errors" />

            <p>
                <label for="email">Correo electrónico</label>
                <input
                    id="email"
                    v-model="email"
                    type="email"
                    autocomplete="email"
                    required
                />
            </p>
            <UiButton
                type="submit"
                variant="primary"
                size="lg"
                :disabled="pending"
                >Enviar enlace</UiButton
            >
        </form>

        <p><NuxtLink to="/cuenta/ingresar">Volver a ingresar</NuxtLink></p>
    </main>
</template>
