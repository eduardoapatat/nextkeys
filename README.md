# NextKeys

E-commerce de videojuegos: keys digitales, licencias de software y juegos físicos.
Proyecto de portfolio.

> **Tienda demo.** Todos los productos, keys y pagos son ficticios.

## Stack

| Parte | Tecnología | Carpeta |
|---|---|---|
| API + panel admin | Laravel · Inertia · Vue · TypeScript | `apps/api` |
| Tienda pública (SSR) | Nuxt | `apps/web` |
| Servicios | PostgreSQL · Redis · Mailpit (Docker) | `docker-compose.yml` |

## Estructura

```
nextkeys/
├── apps/
│   ├── api/     # Laravel: API REST + panel de administración
│   └── web/     # Nuxt: tienda pública
├── docker-compose.yml
└── README.md
```

Cada app es un proyecto independiente con sus propias dependencias.

## Desarrollo local

Requisitos: PHP 8.5, Composer, Node, pnpm y Docker.

```bash
docker compose up -d
```

| Servicio | URL / puerto |
|---|---|
| PostgreSQL | `localhost:5432` (db/usuario `nextkeys`) |
| Redis | `localhost:6379` |
| Mailpit | http://localhost:8025 |

Las instrucciones de cada app están en su propio README.

## Licencia

[MIT](LICENSE)
