# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

The primary user is someone evaluating the author's portfolio: a recruiter or tech lead who opens the public demo and walks through the store for a few minutes to judge craft and judgment. They browse, open a product, add to the cart, sign up and may pay with Webpay's test cards. The store must behave like a real one for a gamer in Chile, because that realism is what they are judging.

## Product Purpose

NextKeys is a portfolio e-commerce for digital game keys, software licenses and physical games. All data and payments are fictitious. It exists to show an end-to-end store built with care: catalog, cart, checkout with Webpay, orders and a key library. Success is a reviewer who finishes the visit convinced the work is production-grade.

## Positioning

- Prices in Chilean pesos (CLP, `es-CL`) and payment with Transbank Webpay Plus; nothing in dollars.
- One store for digital keys, software licenses and physical games.
- Instant delivery: keys arrive by email and wait in "Mi biblioteca", revealed only when the customer asks.
- Every variant states its platforms, region and activation service plainly.

## Operating Context

- Storefront in Nuxt 4 with SSR (`apps/web`), reading the Laravel API (`/api/v1`); no business logic in Nuxt.
- Pages: home, catalog with filters, product page with a variant selector, cart (cookie), checkout to Webpay, payment result, orders, library, and the account pages (sign in, registration, recovery, verification).

## Capabilities and Constraints

- The UI is Spanish only. Product keys are "claves" in the UI.
- The API has no name search, discounts, featured flag or reviews yet (phase 6); the demo seeder has about ten products and no covers.
- Sections without API data are kept as clearly static examples with `#` links; nothing invents prices, discounts, customers or reviews presented as real.
- Withdrawal notice: delivered digital keys are excluded from the derecho a retracto; it is shown before payment.

## Brand Commitments

- Name: NextKeys. No logo, palette or typefaces exist yet.
- Set by the user for the storefront: dark theme, covers as the protagonists, a hover effect on product cards (card tilt or book-cover style), soft motion that honors `prefers-reduced-motion`, responsive.
- Page structure follows the Koparion template's sections (structure only), without the promo strip above the hero.
- Styling with Tailwind CSS v4 and own components (no shadcn-vue).

## Evidence on Hand

- Demo catalog from the API seeder (about ten products, `cover_url` null), platforms, types, regions and activation services from `/api/v1/catalog/filters`.
- No testimonials, press, reviews, sales figures or real customers exist: none may be fabricated.

## Product Principles

- Show the real flow working: every element that looks actionable either works or reads as an example.
- Clarity of what you buy (platform, region, activation, delivery) beats decoration.
- Honest data: real prices and stock from the API, static examples labeled as such.
- Craft is the pitch: details, states and motion are what the reviewer remembers.

## Accessibility & Inclusion

- `prefers-reduced-motion` reduces motion to opacity and color; semantic HTML, keyboard reachable, works without JS where it does today (variant links, forms).
