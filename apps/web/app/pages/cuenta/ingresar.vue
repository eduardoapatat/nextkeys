<script setup lang="ts">
import { login } from '~/api/auth';

definePageMeta({ middleware: 'guest' });
useHead({ title: 'Ingresar' });

const route = useRoute();
const customer = useCustomer();
const form = reactive({ email: '', password: '', remember: false });
const errors = ref<string[]>([]);
const pending = ref(false);

async function submit() {
    pending.value = true;
    errors.value = [];

    try {
        customer.value = await login(form);
        await navigateTo(safeRedirect(route.query.redirect));
    } catch (error) {
        errors.value = formErrorMessages(error);
    } finally {
        pending.value = false;
    }
}
</script>

<template>
    <main>
        <h1>Ingresar</h1>

        <form @submit.prevent="submit">
            <FormErrors :messages="errors" />

            <p>
                <label for="email">Correo electrónico</label><br />
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required
                />
            </p>
            <p>
                <label for="password">Contraseña</label><br />
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    required
                />
            </p>
            <p>
                <label>
                    <input v-model="form.remember" type="checkbox" />
                    Recordarme
                </label>
            </p>
            <button type="submit" :disabled="pending">Ingresar</button>
        </form>

        <p>
            <NuxtLink to="/cuenta/recuperar"
                >¿Olvidaste tu contraseña?</NuxtLink
            >
        </p>
        <p>
            ¿No tienes cuenta?
            <NuxtLink to="/cuenta/registro">Crear una cuenta</NuxtLink>
        </p>
    </main>
</template>
