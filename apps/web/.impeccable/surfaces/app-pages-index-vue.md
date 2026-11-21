---
version: 1
slug: 'app-pages-index-vue'
primary_target: 'app/pages/index.vue'
related_targets: []
---

# Storefront home

Scope: `/` (home), mode Persuade. The other storefront pages (catalog, product, cart, checkout, result, orders, library, account) are Operate and inherit this world.

Audience: a recruiter or tech lead walking the demo for a few minutes; it must read as a real Chilean game-key store. Action: open a product and add it to the cart. Proof: real catalog, CLP prices, platforms, region and activation from the API. Constraints: Koparion section structure (no promo strip above the hero), dark, covers lead, card hover tilt, reduced motion, Spanish UI, no invented discounts or reviews.

## Redesign (2026-11-21)

The user replaced the world below after building it: the page is now light. White ground with navy text, turquoise brand sections (#1e758a) for the header, banner slider, benefits and footer, a coral accent (#e04a2a, white bold text) for primary actions and the current menu item, Rubik and Sofia Sans Condensed, key-store product cards (price and "Agregar" at the foot) and covers with their platform's official band. On 2026-11-21 the display case and the shelf's side menu were removed; the home is now: banner, Juegos shelf, a full-width turquoise band of drawn category tiles, Programas, ¿Cómo activo mi clave?, benefits, preguntas frecuentes. DESIGN.md holds the current system; the contract below is the original direction, kept as history.

## Direction contract (original, superseded)

THESIS: The store is the counter of a neighborhood cibercafé at midnight: a lit glass display case of games, a banner slider over the counter and a thermal receipt. It refuses the category default of a black page and one neon accent. (The original contract also refused a carousel hero; on 2026-11-21 the user asked for a banner slider in place of the "Precios de hoy" whiteboard, so the slider is an unlit band above the case and the case keeps the light.)

OWN-WORLD: Night-room ink ground (#0f1319) and dark melamine panels (#1d232c); the display case is the only bright zone, lit from above. Thermal-paper cream (#efe8d8) for receipts (cart, order, result), sticker yellow (#f2c230) for price stickers and the primary action, CRT cool blue (#9cc8d6) for active and focus. Condensed signage caps for headings and numbered "puestos", a workhorse sans for UI, a monospace for receipts and codes; marker hand only on the error page's whiteboard sign. Cards are numbered stations on one strict grid; platform, region and activation always sit in the same slots.

STORY: The visitor sees a lit case of real games with real CLP prices, understands at once what each one is and where it activates, opens one and adds it; the cart prints as a receipt.

FIRST VIEWPORT: Header bar (logo, search on the counter, account, cart with count). Below, the lit display case fills about two thirds of the width: one lead cover at billing scale plus four smaller ones, each with a yellow price sticker and an "Agregar" button. Above it, a banner slider (unlit ink-2 band): the deal of the day with its countdown first, then Webpay and library slides, with dots, pause and arrows. Primary action: "Agregar al carrito" on the lead cover.

FORM: Cibercafé de barrio, position 6 on the ordered list, seed:a6d1fe050f5a8abe. Raises: one strict station grid (ASCII), one truth per field in fixed slots (six-pack), billing-rank cover scale (combo sheet), sections stacked as light bands from the lit case to quieter zones (cyclorama).

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
