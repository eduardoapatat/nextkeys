<script setup lang="ts">
/**
 * "¿Cómo activo mi clave?": one tab per activation service the catalog
 * sells, each with its official mark and the steps to redeem a code there.
 * Static content: the steps are the services' own, not ours.
 */
interface Guide {
    id: string;
    name: string;
    mark: PlatformMarkName;
    steps: string[];
}

const guides: Guide[] = [
    {
        id: 'steam',
        name: 'Steam',
        mark: 'steam',
        steps: [
            'Abre Steam en tu computador e inicia sesión.',
            'Haz clic en «Añadir un juego» (abajo a la izquierda) y elige «Activar un producto en Steam».',
            'Pega la clave y confirma: el juego queda en tu biblioteca, listo para descargar.',
        ],
    },
    {
        id: 'playstation',
        name: 'PlayStation Store',
        mark: 'playstation',
        steps: [
            'Inicia sesión con una cuenta de PlayStation de la misma región que la clave.',
            'En PlayStation Store, abre el menú «…» y elige «Canjear código». En la web, está en tu perfil.',
            'Escribe el código de 12 caracteres y confirma.',
        ],
    },
    {
        id: 'xbox',
        name: 'Xbox',
        mark: 'xbox',
        steps: [
            'Inicia sesión con tu cuenta Microsoft.',
            'En la consola, ve a Tienda y elige «Canjear»; en el navegador, entra a redeem.microsoft.com.',
            'Escribe el código de 25 caracteres y confirma.',
        ],
    },
    {
        id: 'nintendo',
        name: 'Nintendo eShop',
        mark: 'switch',
        steps: [
            'Abre Nintendo eShop en tu consola y elige tu usuario.',
            'Selecciona «Canjear código» en el menú de la izquierda.',
            'Escribe el código de 16 caracteres y confirma: la descarga empieza sola.',
        ],
    },
    {
        id: 'programas',
        name: 'Programas',
        mark: 'windows',
        steps: [
            'Office y Microsoft 365: entra a setup.office.com con tu cuenta Microsoft y escribe la clave.',
            'Windows: abre Configuración › Sistema › Activación y elige «Cambiar clave de producto».',
            'Antivirus: escribe la clave en la cuenta de la marca (Norton Account, My Kaspersky o ESET HOME) y descarga desde ahí.',
        ],
    },
];

const active = ref(guides[0]!.id);
const tabRefs = ref<HTMLButtonElement[]>([]);

// Arrow keys move between tabs (WAI-ARIA tabs pattern, automatic activation).
function onKeydown(event: KeyboardEvent, index: number) {
    const last = guides.length - 1;
    const next = {
        ArrowRight: index === last ? 0 : index + 1,
        ArrowLeft: index === 0 ? last : index - 1,
        Home: 0,
        End: last,
    }[event.key];
    if (next === undefined) return;
    event.preventDefault();
    active.value = guides[next]!.id;
    tabRefs.value[next]?.focus();
}

const symbol = (mark: PlatformMarkName) => platformMarks[mark][0]!;
</script>

<template>
    <section id="activar" aria-labelledby="activation-heading">
        <h2
            id="activation-heading"
            class="text-4xl text-fg uppercase md:text-5xl"
        >
            ¿Cómo activo mi clave?
        </h2>
        <p class="mt-1 text-fg-muted">
            Tu clave está en
            <NuxtLink to="/biblioteca" class="text-brand underline"
                >Mi biblioteca</NuxtLink
            >
            y en el correo de confirmación. Se activa en la tienda que dice el
            producto.
        </p>

        <div
            role="tablist"
            aria-label="Tiendas"
            class="-mx-4 mt-6 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none]"
        >
            <button
                v-for="(guide, index) in guides"
                :id="`guide-tab-${guide.id}`"
                :key="guide.id"
                ref="tabRefs"
                type="button"
                role="tab"
                :aria-selected="active === guide.id"
                :aria-controls="`guide-panel-${guide.id}`"
                :tabindex="active === guide.id ? 0 : -1"
                class="pressable flex h-12 shrink-0 items-center gap-2.5 rounded-control border px-4 font-display text-lg font-extrabold tracking-wide whitespace-nowrap uppercase"
                :class="
                    active === guide.id
                        ? 'border-brand bg-brand text-on-cover'
                        : 'border-line text-fg-muted hover:border-line-strong hover:text-fg'
                "
                @click="active = guide.id"
                @keydown="onKeydown($event, index)"
            >
                <svg
                    aria-hidden="true"
                    :viewBox="symbol(guide.mark).viewBox"
                    class="h-5 w-auto"
                    fill="currentColor"
                >
                    <path :d="symbol(guide.mark).d" />
                </svg>
                {{ guide.name }}
            </button>
        </div>

        <div
            v-for="guide in guides"
            :id="`guide-panel-${guide.id}`"
            :key="guide.id"
            role="tabpanel"
            :aria-labelledby="`guide-tab-${guide.id}`"
            :hidden="active !== guide.id"
            class="mt-4"
        >
            <ol class="grid gap-3 md:grid-cols-3 md:gap-4">
                <li
                    v-for="(step, index) in guide.steps"
                    :key="step"
                    class="flex gap-4 rounded-card border border-line bg-bg-subtle p-5"
                >
                    <span
                        aria-hidden="true"
                        class="font-display text-4xl leading-none font-extrabold text-accent"
                        >{{ index + 1 }}</span
                    >
                    <span class="text-fg">{{ step }}</span>
                </li>
            </ol>
        </div>
    </section>
</template>
