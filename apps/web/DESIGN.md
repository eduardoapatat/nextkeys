---
name: NextKeys
description: A neighborhood cibercafé counter at midnight, selling game keys, licenses and boxed games in Chilean pesos.
colors:
    ink: '#0f1319'
    ink-2: '#141922'
    panel: '#1d232c'
    panel-2: '#262e39'
    line: '#313a47'
    line-strong: '#4a5564'
    chalk: '#ece7dc'
    chalk-muted: '#a7afb9'
    chalk-faint: '#8a94a1'
    paper: '#efe8d8'
    paper-ink: '#22201b'
    paper-muted: '#5d5749'
    paper-line: '#cfc6b2'
    sticker: '#f2c230'
    sticker-hover: '#f7d35c'
    sticker-ink: '#1a1405'
    crt: '#9cc8d6'
    crt-deep: '#1f3640'
    danger: '#e2735f'
    danger-deep: '#3a1f1b'
    ok: '#8fc79a'
    ok-deep: '#1c3324'
    board: '#e6e9e4'
    board-ink: '#1d2b4a'
    board-red: '#b3362b'
    board-muted: '#4d5a6e'
    board-line: '#c6ccc5'
    cover-1: '#6b2d3a'
    cover-2: '#234b6b'
    cover-3: '#2f5a3c'
    cover-4: '#6a4a1f'
    cover-5: '#4b3270'
    cover-6: '#1f5a5e'
typography:
    display:
        fontFamily: 'Barlow Condensed, Barlow, ui-sans-serif, sans-serif'
        fontSize: '3.75rem'
        fontWeight: 700
        lineHeight: 1.05
        letterSpacing: '-0.01em'
    headline:
        fontFamily: 'Barlow Condensed, Barlow, ui-sans-serif, sans-serif'
        fontSize: '1.875rem'
        fontWeight: 700
        lineHeight: 1.05
        letterSpacing: '-0.01em'
    title:
        fontFamily: 'Barlow, ui-sans-serif, system-ui, sans-serif'
        fontSize: '1rem'
        fontWeight: 600
        lineHeight: 1.375
    body:
        fontFamily: 'Barlow, ui-sans-serif, system-ui, sans-serif'
        fontSize: '1rem'
        fontWeight: 400
        lineHeight: 1.5
    label:
        fontFamily: 'Barlow Condensed, Barlow, ui-sans-serif, sans-serif'
        fontSize: '1rem'
        fontWeight: 700
        lineHeight: 1
        letterSpacing: '0.025em'
    receipt:
        fontFamily: 'JetBrains Mono, ui-monospace, monospace'
        fontSize: '0.875rem'
        fontWeight: 400
        lineHeight: 1.5
    marker:
        fontFamily: 'Permanent Marker, Barlow Condensed, cursive'
        fontSize: '1.875rem'
        fontWeight: 400
        lineHeight: 1.2
rounded:
    control: '4px'
    card: '6px'
spacing:
    gutter: '16px'
    stack-sm: '12px'
    stack-md: '16px'
    stack-lg: '24px'
    station-gap-x: '20px'
    station-gap-y: '40px'
    section: '64px'
    section-md: '80px'
    container: '80rem'
components:
    button-primary:
        backgroundColor: '{colors.sticker}'
        textColor: '{colors.sticker-ink}'
        typography: '{typography.label}'
        rounded: '{rounded.control}'
        padding: '0 16px'
        height: '44px'
    button-primary-hover:
        backgroundColor: '{colors.sticker-hover}'
    button-secondary:
        backgroundColor: '{colors.panel-2}'
        textColor: '{colors.chalk}'
        typography: '{typography.label}'
        rounded: '{rounded.control}'
        padding: '0 16px'
        height: '44px'
    button-secondary-hover:
        backgroundColor: '{colors.panel}'
    button-ghost:
        textColor: '{colors.chalk-muted}'
        typography: '{typography.label}'
        rounded: '{rounded.control}'
        padding: '0 16px'
        height: '44px'
    button-ghost-hover:
        textColor: '{colors.chalk}'
    input-text:
        backgroundColor: '{colors.panel}'
        textColor: '{colors.chalk}'
        rounded: '{rounded.control}'
        padding: '0 12px'
        height: '44px'
    price-sticker:
        backgroundColor: '{colors.sticker}'
        textColor: '{colors.sticker-ink}'
        rounded: '{rounded.control}'
        padding: '4px 8px'
    price-sticker-out-of-stock:
        backgroundColor: '{colors.panel-2}'
        textColor: '{colors.chalk-muted}'
    thermal-receipt:
        backgroundColor: '{colors.paper}'
        textColor: '{colors.paper-ink}'
        typography: '{typography.receipt}'
        padding: '28px 24px'
    whiteboard:
        backgroundColor: '{colors.board}'
        textColor: '{colors.board-ink}'
        rounded: '{rounded.card}'
        padding: '20px'
    product-cover:
        backgroundColor: '{colors.panel-2}'
        textColor: '{colors.chalk}'
        rounded: '{rounded.card}'
    panel-card:
        backgroundColor: '{colors.panel}'
        textColor: '{colors.chalk}'
        rounded: '{rounded.card}'
        padding: '16px'
---

# Design System: NextKeys

## Overview

**Creative North Star: "The Cibercafé Counter at Midnight"**

The storefront is the counter of a Chilean neighborhood cibercafé after dark. The room is a cool ink ground with dark melamine panels; the only bright zone is the glass display case lit by a tube from above, where game boxes stand on a glass shelf wearing yellow price stickers. Behind the counter hangs a whiteboard written in marker with today's prices, and every purchase prints as a cream thermal receipt with torn edges. Each material has one job, and each job always uses the same material.

Density is that of a working shop, not a showroom: one strict grid of numbered stations ("PC 01", "PC 02"), each with the cover on top and platform, type, region and activation in fixed slots below. Covers lead every listing at 3:4 game-case proportion. When a product has no uploaded cover, the system prints one from its own data: a deep per-product tint, a platform band, the title in condensed caps, type and region at the foot. Motion is soft and physical. Covers tilt toward the pointer like a box picked up from the shelf, buttons press to 0.97, lists rise in with a short stagger, and everything falls back to opacity and color under `prefers-reduced-motion`.

The world refuses the category default for game-key stores: a black page with a single neon accent and a carousel hero. Light comes from objects (the case, the sticker, the CRT glow), never from neon gradients.

**Key Characteristics:**

- Night-room ink ground; the display case is the only lit zone.
- Sticker yellow marks prices and the one primary action per view.
- CRT blue means "active" and "focused", nothing else.
- Thermal paper and monospace for every money summary: cart, checkout, payment result, order.
- Condensed signage caps for headings and buttons, a plain sans for reading, marker only on the whiteboard sign.
- Numbered stations on one grid, facts always in the same slot.

## Colors

A cool, dark room with four lit materials (case light, sticker, receipt, whiteboard) and one signal color for focus.

### Primary

- **Sticker Yellow** (sticker): price stickers, the primary button, the cart count when it is not empty, active tab underline, text selection, caret and native accent color. Hover lifts to **Fresh Sticker** (sticker-hover); text on it is **Sticker Ink** (sticker-ink), never white.

### Secondary

- **CRT Glow** (crt): every focus ring (2px outline, 2px offset), focused input borders, hovered category tiles, and in-text links ("Ver carrito", "filtra el catálogo"). **CRT Deep** (crt-deep) is its dark fill for active backgrounds.

### Tertiary

- **Thermal Paper** (paper) with **Receipt Ink** (paper-ink), **Faded Print** (paper-muted) and **Dashed Rule** (paper-line): the receipt surface only.
- **Whiteboard** (board) with **Marker Navy** (board-ink), **Marker Red** (board-red), **Board Grey** (board-muted) and **Board Rule** (board-line): the home whiteboard only. Marker Red marks the deal-of-the-day frame and link hovers on the board.
- **Cover Tints** (cover-1 to cover-6: oxblood, harbor blue, felt green, tobacco, cartridge violet, deep teal): fills of generated covers, picked deterministically from the product slug. They never appear outside a cover.

### Neutral

- **Night Room** (ink): the page ground and the header.
- **Back Room** (ink-2): the nav bar, the newsletter and footer bands, form and filter panels.
- **Melamine** (panel) and **Melamine Raised** (panel-2): cards, inputs, secondary buttons, cover placeholders, the category sidebar header.
- **Seam** (line) and **Hard Seam** (line-strong): hairline dividers and control borders.
- **Chalk** (chalk): primary text, about 15:1 on ink. **Chalk Muted** (chalk-muted, 8.4:1) for secondary text, **Chalk Faint** (chalk-faint, 6:1 on ink, 5:1 on panels) for station numbers, labels in definition lists and placeholders.
- **Status**: **Warning Coral** (danger) on **Ember** (danger-deep) for errors; **Felt Green** (ok) on **Moss** (ok-deep) for confirmations. Used as a 50% border plus deep fill, never as large solid areas.

### Named Rules

**The One Sticker Rule.** Yellow fills only price stickers and the single primary action of a view. A second yellow button on the same screen means one of them is secondary.

**The Signal Rule.** CRT blue means focus or "you are here". Do not use it for decoration or for prices.

**The Material Rule.** Paper belongs to receipts, the board to the whiteboard, cover tints to covers. A material never leaves its object.

## Typography

**Display Font:** Barlow Condensed (with Barlow, ui-sans-serif)
**Body Font:** Barlow (with ui-sans-serif, system-ui)
**Label/Mono Font:** JetBrains Mono (with ui-monospace) for receipts, codes, references and station numbers; Permanent Marker (with Barlow Condensed) for whiteboard notes.

**Character:** Shop signage over a workhorse sans. The condensed caps read like the lettering on a counter and on game boxes; Barlow keeps product facts plain; the monospace is the receipt printer; the marker is a person writing on the board.

All fonts are self-hosted woff2 (latin and latin-ext) in `app/assets/css/fonts.css`: Barlow 400/500/600, Barlow Condensed 500/700, JetBrains Mono 400/600, Permanent Marker 400.

### Hierarchy

- **Display** (700, 3rem mobile to 3.75rem desktop, up to 4.5rem on the product page, line-height 1.05, uppercase): one page title per view ("CARRITO", "MI BIBLIOTECA", the product name).
- **Headline** (700, 1.875rem to 3rem, line-height 1.05, uppercase): section headings ("ELIGE TU PLATAFORMA"), the display-case heading, category tiles.
- **Title** (Barlow 600, 1rem, line-height 1.375, sentence case): product names on station cards and cart lines.
- **Body** (Barlow 400, 1rem, line-height 1.5, pretty wrapping): descriptions and notes; facts in definition lists drop to 0.875rem.
- **Label** (Barlow Condensed 700, 0.875 to 1.125rem, tracking 0.025em, uppercase): buttons, nav links, tabs, the cart pill.
- **Receipt** (JetBrains Mono 400, 0.875rem; header line 0.75rem with 0.2em tracking, uppercase): everything printed on paper, order references and key codes.
- **Marker** (Permanent Marker 400, 1.5 to 1.875rem, sentence case): the error page's whiteboard sign only.

### Named Rules

**The Tabular Price Rule.** Every price, count, countdown and reference uses tabular figures (`data`, `time` and `.tabular` get them by default) and comes from the API in CLP.

**The Marker Stays on the Board Rule.** Permanent Marker appears only on the whiteboard sign of the error page. Nowhere else is hand-written.

## Layout

One centered container (80rem max) with a 16px side gutter at every width. The home stacks its zones from bright to quiet: the banner slider and the lit display case below it (24px apart), then platform tiles, then the shelf with its category sidebar (15rem) and tabs, then benefits, then the newsletter and footer on the darker back-room band. Zones are separated by 64px (80px from `md`).

Listings use the station grid: 2 columns, 3 from `sm`, 4 from `xl`, with 20px between columns and 40px between rows so the fact slots under each cover never crowd the next cover. Station facts are a two-column definition grid: platforms span the row, type left and region right, activation spans the last row.

Money pages pair a working column with a receipt: the cart lines on the left, the receipt on the right from `lg`, stacking below on small screens. Account pages are one narrow column (28rem) with the form on a back-room panel.

Breakpoints are Tailwind's defaults (`sm` 640px, `md` 768px, `lg` 1024px, `xl` 1280px). Below `md` the platform nav collapses into a drawer that slides in from the left; the search moves to its own row under the logo; catalog filters fold into a disclosure that stays open from `lg`.

## Elevation & Depth

Depth is physical and comes from one light source: the tube above the display case. Surfaces in the room are flat tonal steps (ink, ink-2, panel, panel-2); objects that sit in the light (covers, stickers, receipts, the whiteboard) cast soft, downward shadows. Nothing glows except the case tube and the cover glare.

### Shadow Vocabulary

- **Case** (`0 24px 48px -24px rgb(0 0 0 / 0.7), 0 2px 6px rgb(0 0 0 / 0.35)`): the display case, covers at rest, the fanned banner covers.
- **Lift** (`0 18px 32px -12px rgb(0 0 0 / 0.65), 0 4px 10px rgb(0 0 0 / 0.4)`): a cover picked up on hover.
- **Sticker** (`0 3px 8px -2px rgb(0 0 0 / 0.55)`): price stickers and the primary button, which is a sticker.
- **Receipt** (`drop-shadow(0 12px 18px rgb(0 0 0 / 0.45))` as a filter, so it follows the torn edge).
- **Case light**: a top-down white wash (7% to transparent at 45%) over a panel-to-ink-2 gradient, with a 1px inner highlight; the tube is a 2px white line with a soft 18px bloom; the glass shelf is a 12px translucent strip under the covers.

### Named Rules

**The One Lit Zone Rule.** Only the display case receives the overhead light treatment. Other sections stay flat on the room's tonal steps.

**The Shadow Follows the Object Rule.** Shadows belong to things you could pick up. Panels, nav bars and form sections have borders, not shadows.

## Shapes

Small, practical corners: 4px for controls (buttons, inputs, stickers, pills) and 6px for cards, covers, the case and panels. Borders are 1px hairlines in Seam or Hard Seam; dashed borders mark paper-like or provisional things (receipt rules, the empty state, the deal frame on the board). The whiteboard has a 4px Melamine Raised frame like an aluminum rim. Price stickers sit rotated (-3deg small, -2deg large) and overlap the cover's lower right corner. The receipt is clipped with an 8px zig-zag tear at top and bottom.

## Components

### Buttons

Signage keys: condensed bold caps, solid and pressable.

- **Shape:** gently squared (4px).
- **Primary:** Sticker Yellow with Sticker Ink text and the sticker shadow; heights 36, 44 and 52px with 12, 16 and 24px side padding.
- **Secondary:** Melamine Raised with a Hard Seam border and Chalk text; on hover the fill drops to Melamine and the border brightens to Chalk Faint.
- **Ghost:** bare Chalk Muted label that brightens to Chalk ("Ver ficha", "Ver todo").
- **Press / Focus:** every pressable scales to 0.97 over 160ms with the out curve; focus is the CRT ring. Disabled is 50% opacity with a not-allowed cursor.
- **Icon + label:** icons are inline SVG at 16 to 22px; compact add buttons show only the cart icon on mobile and "Agregar" from `sm`.

### Price Sticker

The yellow price label stuck on a box: condensed bold CLP figure, tabular, rotated a few degrees, overlapping the cover. Out of stock it turns Melamine Raised with Chalk Muted text and a "Sin stock" line. There are no strike-through or "before" prices.

### Station Card

A product as a numbered spot on the counter. Cover (3:4, tilting) with its sticker, then "PC 01" in faint monospace, the title in Barlow 600, and three fixed fact slots: platforms; type and region; "Se activa en …". The whole card is one link; hover turns the title yellow and lifts the cover.

### Product Cover

A game-case front at 3:4 with 6px corners. Uploaded covers fill it; otherwise a generated cover prints the platform band (a 55% ink strip), the title in condensed caps at the foot, and type and region in small caps, on a cover tint chosen from the slug.

### Cover Tilt

Wraps a cover; on fine hover pointers without reduced motion it rotates up to 7deg (9deg for the lead cover) in 900px perspective, rises 4px, swaps the case shadow for lift, and slides a soft white glare under the pointer. It returns over 260ms. On touch or reduced motion the cover only changes shadow.

### Thermal Receipt

Cream paper, monospace print, torn zig-zag edges, dashed rules between blocks and a centered tracked header ("NEXTKEYS · DETALLE"). It holds the cart summary, the checkout total, the payment result and the order detail. The primary action may sit on the paper.

### Banner slider

Home only, first zone: a dark ink-2 band (not lit: the display case stays the one lit zone) with three slides on a scroll-snap track. The deal of the day (sticker-yellow label, monospace countdown to Santiago midnight, the price sticker and the tilted cover), "Paga con Webpay" with a thermal receipt slip, and "Tu clave, al instante" with three fanned covers. Dots (the active one in sticker yellow), pause and arrow buttons sit on the bottom edge. It advances every 7s, pauses on hover, focus and its pause button, never moves on its own under reduced motion, and off-screen slides are `inert`.

### Whiteboard sign

Error page only: an off-white board sign in marker ("Fuera de servicio").

### Inputs / Fields

- **Style:** 44px tall, Melamine fill, Hard Seam 1px border, 4px corners, 12px side padding, Chalk text, Chalk Faint placeholder. Set once for all text, email and password fields in the base layer.
- **Focus:** the border turns CRT blue (no extra outline). Checkboxes and native selects take the sticker accent.
- **Error / Status:** errors in a 6px panel with a 50% Warning Coral border on Ember; confirmations the same in Felt Green on Moss.

### Navigation

The header is the counter: logo, search on a Melamine field, account links in Chalk Muted, and the cart as a secondary key whose count pill turns yellow once the cart has items. Under it, the Back Room nav bar lists Catálogo, platform families and product types as condensed caps links (Chalk Muted to Chalk), grouped by 1px vertical seams and horizontally scrollable. Tabs on the shelf use the same caps with a 2px yellow underline on the active one. On small screens a drawer slides in from the left (200ms out, 280ms in on a drawer curve).

### Empty State

A dashed Hard Seam box with a condensed caps message and one way forward.

## Do's and Don'ts

### Do:

- **Do** keep the page on Night Room (#0f1319) and let only the display case carry the overhead light.
- **Do** put every price on a Sticker Yellow sticker or in tabular figures, in CLP from the API.
- **Do** print every money summary (cart, checkout, result, order) on the thermal receipt in JetBrains Mono.
- **Do** keep a product's facts in the same slots everywhere: platforms, then type and region, then activation.
- **Do** use CRT blue (#9cc8d6) for focus rings and focused borders on every interactive element.
- **Do** give pressable elements the 0.97 press and reduce all motion to opacity and color under `prefers-reduced-motion`.
- **Do** stagger list entrances with the motion helper (0, 40, 70, 95… ms, capped at 150ms) and the 260ms rise.

### Don't:

- **Don't** use more than one Sticker Yellow action per view.
- **Don't** add neon gradients, glowing accents or a carousel hero; light comes from the case tube and the objects in it.
- **Don't** use Permanent Marker outside the whiteboard, or paper and board colors outside their objects.
- **Don't** show "before" prices, invented discounts, ratings or reviews.
- **Don't** put shadows on panels, nav bars or form sections; use borders and tonal steps.
- **Don't** use white text on Sticker Yellow; text on it is Sticker Ink.
