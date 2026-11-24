<script setup lang="ts">
import type { Address } from '~/api/schemas';
import {
    type AddressInput,
    createAddress,
    deleteAddress,
    fetchAddresses,
    fetchShipping,
    updateAddress,
} from '~/api/addresses';

definePageMeta({ middleware: 'auth' });
useHead({ title: 'Mis direcciones' });

/** The API keeps at most this many per customer. */
const maxAddresses = 5;

const customer = useCustomer();
const route = useRoute();

const [{ data: addresses, refresh }, { data: shipping }] = await Promise.all([
    useAsyncData('addresses', fetchAddresses),
    useAsyncData('shipping', fetchShipping),
]);

const emptyForm = (): AddressInput => ({
    recipient_name: customer.value?.name ?? '',
    phone: '',
    region: '',
    commune: '',
    street: '',
    street_number: '',
    apartment: '',
});

const form = reactive<AddressInput>(emptyForm());
/** The address being edited, or null while adding a new one. */
const editing = ref<string | null>(null);
const confirmingDelete = ref<string | null>(null);
const errors = ref<string[]>([]);
const notice = ref('');
const pending = ref(false);

const communes = computed(
    () =>
        shipping.value?.regions.find((region) => region.code === form.region)
            ?.communes ?? [],
);
const canAdd = computed(
    () =>
        (addresses.value?.length ?? 0) < maxAddresses || editing.value !== null,
);

/** A commune belongs to one region: changing the region clears it. */
function selectRegion(code: string) {
    form.region = code;

    if (!communes.value.includes(form.commune)) {
        form.commune = '';
    }
}

function edit(address: Address) {
    editing.value = address.public_id;
    confirmingDelete.value = null;
    errors.value = [];
    notice.value = '';
    Object.assign(form, {
        recipient_name: address.recipient_name,
        phone: address.phone,
        region: address.region.code,
        commune: address.commune,
        street: address.street,
        street_number: address.street_number,
        apartment: address.apartment ?? '',
    });
}

function reset() {
    editing.value = null;
    Object.assign(form, emptyForm());
}

async function save() {
    pending.value = true;
    errors.value = [];
    notice.value = '';

    try {
        if (editing.value === null) {
            await createAddress({ ...form });
            notice.value = 'Guardamos tu dirección.';
        } else {
            await updateAddress(editing.value, { ...form });
            notice.value = 'Actualizamos tu dirección.';
        }

        reset();
        await refresh();

        if (route.query.redirect !== undefined) {
            await navigateTo(safeRedirect(route.query.redirect));
        }
    } catch (error) {
        errors.value = formErrorMessages(error);
    } finally {
        pending.value = false;
    }
}

async function remove(address: Address) {
    if (confirmingDelete.value !== address.public_id) {
        confirmingDelete.value = address.public_id;

        return;
    }

    pending.value = true;
    errors.value = [];
    notice.value = '';

    try {
        await deleteAddress(address.public_id);
        confirmingDelete.value = null;

        if (editing.value === address.public_id) {
            reset();
        }

        notice.value = 'Borramos la dirección.';
        await refresh();
    } catch (error) {
        errors.value = formErrorMessages(error);
    } finally {
        pending.value = false;
    }
}
</script>

<template>
    <main v-if="customer" class="account-page">
        <p class="text-sm">
            <NuxtLink to="/cuenta">Mi cuenta</NuxtLink>
        </p>
        <h1>Mis direcciones</h1>

        <FormErrors :messages="errors" />
        <p v-if="notice" role="status">{{ notice }}</p>

        <ul
            v-if="addresses && addresses.length > 0"
            aria-label="Direcciones guardadas"
            class="flex flex-col gap-3"
        >
            <li
                v-for="address in addresses"
                :key="address.public_id"
                class="flex flex-col gap-3 rounded-card border border-line bg-surface p-5"
            >
                <address class="text-fg not-italic">
                    <strong class="block">{{ address.recipient_name }}</strong>
                    {{ address.street }} {{ address.street_number
                    }}<template v-if="address.apartment"
                        >, {{ address.apartment }}</template
                    ><br />
                    {{ address.commune }}, {{ address.region.name }}<br />
                    <span class="tabular text-fg-muted">{{
                        address.phone
                    }}</span>
                </address>
                <div class="flex flex-wrap gap-2">
                    <UiButton
                        size="sm"
                        variant="secondary"
                        :disabled="pending"
                        @click="edit(address)"
                    >
                        Editar
                    </UiButton>
                    <UiButton
                        size="sm"
                        variant="ghost"
                        :disabled="pending"
                        @click="remove(address)"
                    >
                        {{
                            confirmingDelete === address.public_id
                                ? 'Confirmar borrado'
                                : 'Borrar'
                        }}
                    </UiButton>
                </div>
            </li>
        </ul>
        <p v-else>
            Aún no guardas direcciones. Agrega una para comprar juegos físicos.
        </p>

        <form
            v-if="canAdd"
            :aria-label="
                editing === null ? 'Agregar dirección' : 'Editar dirección'
            "
            @submit.prevent="save"
        >
            <h2>
                {{
                    editing === null ? 'Agregar dirección' : 'Editar dirección'
                }}
            </h2>
            <p>
                <label for="recipient_name">Quién recibe</label>
                <input
                    id="recipient_name"
                    v-model="form.recipient_name"
                    type="text"
                    autocomplete="name"
                    maxlength="100"
                    required
                />
            </p>
            <p>
                <label for="phone">Celular</label>
                <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    autocomplete="tel"
                    placeholder="+56 9 1234 5678"
                    maxlength="20"
                    required
                />
            </p>
            <p>
                <label for="region">Región</label>
                <select
                    id="region"
                    :value="form.region"
                    required
                    @change="
                        selectRegion(($event.target as HTMLSelectElement).value)
                    "
                >
                    <option value="" disabled>Elige una región</option>
                    <option
                        v-for="region in shipping?.regions ?? []"
                        :key="region.code"
                        :value="region.code"
                    >
                        {{ region.name }}
                    </option>
                </select>
            </p>
            <p>
                <label for="commune">Comuna</label>
                <select
                    id="commune"
                    v-model="form.commune"
                    required
                    :disabled="form.region === ''"
                >
                    <option value="" disabled>Elige una comuna</option>
                    <option
                        v-for="commune in communes"
                        :key="commune"
                        :value="commune"
                    >
                        {{ commune }}
                    </option>
                </select>
            </p>
            <div class="grid grid-cols-[1fr_8rem] gap-3">
                <p class="flex flex-col gap-1.5">
                    <label for="street">Calle</label>
                    <input
                        id="street"
                        v-model="form.street"
                        type="text"
                        autocomplete="address-line1"
                        maxlength="120"
                        required
                    />
                </p>
                <p class="flex flex-col gap-1.5">
                    <label for="street_number">Número</label>
                    <input
                        id="street_number"
                        v-model="form.street_number"
                        type="text"
                        maxlength="20"
                        required
                    />
                </p>
            </div>
            <p>
                <label for="apartment">Depto, casa u oficina (opcional)</label>
                <input
                    id="apartment"
                    v-model="form.apartment"
                    type="text"
                    autocomplete="address-line2"
                    maxlength="40"
                />
            </p>
            <div class="flex flex-wrap gap-2">
                <UiButton type="submit" variant="primary" :disabled="pending">
                    {{
                        editing === null
                            ? 'Guardar dirección'
                            : 'Guardar cambios'
                    }}
                </UiButton>
                <UiButton
                    v-if="editing !== null"
                    variant="ghost"
                    :disabled="pending"
                    @click="reset"
                >
                    Cancelar
                </UiButton>
            </div>
        </form>
        <p v-else>
            Tienes {{ maxAddresses }} direcciones guardadas, el máximo. Borra
            una para agregar otra.
        </p>
    </main>
</template>
