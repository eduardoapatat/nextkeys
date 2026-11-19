# Roadmap

Estado general del proyecto. Se actualiza en el mismo commit que cierra cada tarea.
Las fases terminadas se resumen en una línea; el detalle vive en el historial de git.

## Estado actual

- **Fase en curso:** 1. Catálogo
- **Siguiente tarea:** diseñar el dominio del catálogo en `/plan` (enums, modelos, migraciones, seeders).

## Fases

- [x] **0. Base:** repositorio, Docker (Postgres/Redis/Mailpit), `apps/api` con starter kit Vue, PHPStan `max`, tests sobre Postgres, CI y Dependabot.
- [ ] **1. Catálogo**
  - [ ] Enums: `ProductType` (`digital_key`, `license`, `physical`), plataformas y regiones
  - [ ] Modelos y migraciones: Platform, Region, Product, GameKey (key cifrada)
  - [ ] Factories y seeders con datos ficticios
  - [ ] CRUD de productos en el panel admin
  - [ ] Importación masiva de keys por CSV
- [ ] **2. API pública del catálogo:** `/api/v1` de solo lectura (listado con filtros y ficha) con API Resources.
- [ ] **3. Pedidos y pagos:** Order/OrderItem, reserva de stock con `SKIP LOCKED`, interfaz `PaymentGateway` + Stripe en modo prueba, webhooks y entrega de keys por cola y correo.
- [ ] **4. Tienda Nuxt (`apps/web`):** catálogo, ficha, carrito, autenticación con Sanctum, checkout y "Mi biblioteca".
- [ ] **5. Productos físicos:** direcciones, envíos y estados del despacho.
- [ ] **6. Extras:** búsqueda con Meilisearch, wishlist, reseñas, cupones y dashboard de ventas.
- [ ] **7. Publicación:** despliegue, demo pública y README de portfolio (capturas, GIF, diagrama).

## Decisiones tomadas

- Monorepo con dos apps independientes, sin pnpm workspace en la raíz (se reevalúa si se comparten tipos).
- Postgres también en tests: la reserva de keys depende de bloqueos de fila que SQLite no tiene.
- Instrucciones para agentes en `AGENTS.md`; no se usa `CLAUDE.md`.
- Comentarios de código en inglés; commits y documentación en español.
