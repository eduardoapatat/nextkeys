# NextKeys

Video game e-commerce: digital keys, software licenses and physical games.
Portfolio project.

> **Demo store.** All products, keys and payments are fictitious.

## Stack

| Part | Technology | Folder |
|---|---|---|
| API + admin panel | Laravel · Inertia · Vue · TypeScript | `apps/api` |
| Public storefront (SSR) | Nuxt | `apps/web` |
| Services | PostgreSQL · Redis · Mailpit (Docker) | `docker-compose.yml` |

## Structure

```
nextkeys/
├── apps/
│   ├── api/     # Laravel: REST API + admin panel
│   └── web/     # Nuxt: public storefront
├── docs/        # Roadmap and project docs
├── docker-compose.yml
└── README.md
```

Each app is an independent project with its own dependencies.

## Local development

Requirements: PHP 8.5, Composer, Node, pnpm and Docker.

```bash
docker compose up -d
```

| Service | URL / port |
|---|---|
| PostgreSQL | `localhost:5432` (db/user `nextkeys`) |
| Redis | `localhost:6379` |
| Mailpit | http://localhost:8025 |

Each app has its own README with setup instructions.

## License

[MIT](LICENSE)
