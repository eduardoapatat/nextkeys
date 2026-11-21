<script setup lang="ts">
import { resetPassword } from '~/api/auth';

useHead({ title: 'Restablecer contraseña' });

const route = useRoute();
const token = typeof route.query.token === 'string' ? route.query.token : '';
const email = typeof route.query.email === 'string' ? route.query.email : '';

const form = reactive({ password: '', password_confirmation: '' });
const errors = ref<string[]>([]);
const done = ref(false);
const pending = ref(false);

async function submit() {
    pending.value = true;
    errors.value = [];

    try {
        await resetPassword({ token, email, ...form });
        done.value = true;
    } catch (error) {
        errors.value = formErrorMessages(error);
    } finally {
        pending.value = false;
    }
}
</script>

<template>
    <main class="account-page">
        <h1>Restablecer contraseña</h1>

        <template v-if="done">
            <p role="status">Tu contraseña cambió.</p>
            <p><NuxtLink to="/cuenta/ingresar">Ingresar</NuxtLink></p>
        </template>
        <p v-else-if="!token || !email">
            El enlace está incompleto.
            <NuxtLink to="/cuenta/recuperar">Pide uno nuevo</NuxtLink>.
        </p>
        <form v-else @submit.prevent="submit">
            <FormErrors :messages="errors" />

            <p>Cuenta: {{ email }}</p>
            <p>
                <label for="password">Contraseña nueva</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    required
                />
            </p>
            <p>
                <label for="password_confirmation">Repite la contraseña</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                />
            </p>
            <UiButton
                type="submit"
                variant="primary"
                size="lg"
                :disabled="pending"
            >
                Cambiar contraseña
            </UiButton>
        </form>
    </main>
</template>
