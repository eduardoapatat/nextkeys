# NextKeys

E-commerce de portfolio: keys digitales, licencias de software y juegos físicos. Datos y pagos ficticios.

- `apps/api`: Laravel 13 + Inertia v3 + Vue + TypeScript. API REST para la tienda y panel admin (starter kit Vue).
- `apps/web`: Nuxt con SSR, la tienda pública. Todavía no existe.
- Servicios en `docker-compose.yml`: PostgreSQL 18, Redis 8 y Mailpit.

## Comandos

- Servicios: `docker compose up -d` (desde la raíz).
- Dev API: `composer dev` en `apps/api`.
- **Verificación (único comando):** `composer ci:check` en `apps/api`, que corre ESLint, vue-tsc, Pint, PHPStan y Pest. Es lo mismo que ejecuta el CI.

## Arquitectura y límites

- Laravel es la única fuente de verdad. Nuxt no contiene lógica de negocio: solo consume `/api/v1`.
- Rutas: `routes/web.php` es para el panel admin (Inertia) y `routes/api.php` (`/api/v1`) para la tienda, con API Resources.
- Controladores delgados. La lógica de negocio vive en `app/Actions` (un caso de uso por clase).
- El tipo de producto es un enum (`digital_key`, `license`, `physical`), no subclases. Cada tipo define su forma de entrega.
- Los pagos van detrás de una interfaz `PaymentGateway`. El dominio no conoce a Stripe ni a Mercado Pago.
- Las keys se guardan cifradas (cast `encrypted`) y nunca se exponen en listados ni en logs.
- La reserva y asignación de keys usa bloqueo en Postgres (`lockForUpdate` / `SKIP LOCKED`), nunca lectura y escritura sin lock.
- Cada app es un proyecto pnpm independiente. No hay workspace en la raíz.

## Convenciones

- El usuario autenticado se inyecta con `#[CurrentUser] User $user`, no con `$request->user()` (que es nullable).
- Las rutas Inertia se escriben con `Route::get(..., fn () => Inertia::render(...))`, no con `Route::inertia()`, que PHPStan tipa como `mixed`.
- Comentarios de código y PHPDoc en inglés, en todos los archivos (PHP, TS/Vue, YAML, SQL, configuración).
- Commits en español, con convención `tipo(alcance): descripción`, por ejemplo `feat(api): ...`.

## Checks existentes

- PHPStan con Larastan en nivel `max`, sin baseline (`apps/api/phpstan.neon`).
- Pint, ESLint/Prettier y vue-tsc, todos incluidos en `composer ci:check`.
- CI en `.github/workflows/api.yml`, filtrado por `apps/api/**`, con Postgres como servicio.
- Dependabot en `.github/dependabot.yml` para actions, composer y npm de `apps/api`.

## Trampas conocidas

- Los tests corren contra Postgres (`nextkeys_test`), no SQLite. Docker tiene que estar levantado.
- La base `nextkeys_test` la crea `docker/postgres/init/` solo con un volumen nuevo.
- PHPStan necesita `--memory-limit=1G`, que ya está en el script `types:check`.
- Los workflows de GitHub solo se leen desde `.github/` en la raíz, nunca dentro de `apps/*`.
- El MCP de Boost (`apps/api/.mcp.json`) solo carga si la sesión se abre dentro de `apps/api`.
- El frontend del kit usa Vite+ (`vp`), no `vite`.

## Definición de terminado

- `composer ci:check` en verde.
- Un test nuevo que falla sin el cambio.
- Cambios de UI verificados en el navegador.
