<script setup lang="ts">
import { register } from '~/api/auth';

definePageMeta({ middleware: 'guest' });
useHead({ title: 'Crear cuenta' });

const customer = useCustomer();
const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const errors = ref<string[]>([]);
const pending = ref(false);

async function submit() {
    pending.value = true;
    errors.value = [];

    try {
        customer.value = await register(form);
        await navigateTo('/cuenta');
    } catch (error) {
        errors.value = formErrorMessages(error);
    } finally {
        pending.value = false;
    }
}
</script>

<template>
    <main>
        <h1>Crear cuenta</h1>

        <form @submit.prevent="submit">
            <FormErrors :messages="errors" />

            <p>
                <label for="name">Nombre</label><br />
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    required
                />
            </p>
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
                    autocomplete="new-password"
                    required
                />
            </p>
            <p>
                <label for="password_confirmation">Repite la contraseña</label
                ><br />
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                />
            </p>
            <p>
                Te enviaremos un correo para verificar tu dirección: la
                necesitas verificada para pagar, porque ahí llegan las claves.
            </p>
            <button type="submit" :disabled="pending">Crear cuenta</button>
        </form>

        <p>
            ¿Ya tienes cuenta?
            <NuxtLink to="/cuenta/ingresar">Ingresar</NuxtLink>
        </p>
    </main>
</template>
