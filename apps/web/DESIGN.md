---
name: NextKeys
description: A bright Chilean key store, white floor and turquoise signage, selling game keys, licenses and boxed games in Chilean pesos.
colors:
    bg: '#ffffff'
    bg-subtle: '#f3f6f8'
    surface: '#ffffff'
    surface-2: '#e8eef2'
    line: '#dbe3e8'
    line-strong: '#7f909c'
    fg: '#0b1f33'
    fg-muted: '#3e5163'
    fg-faint: '#586a79'
    brand: '#1e758a'
    brand-strong: '#155566'
    brand-deep: '#0d313b'
    brand-soft: '#e3f2f6'
    accent: '#e04a2a'
    accent-hover: '#c93f22'
    accent-fg: '#ffffff'
    danger: '#b42318'
    danger-soft: '#fdecea'
    ok: '#1f7a3d'
    ok-soft: '#e6f4ea'
    paper: '#f6f1e6'
    paper-ink: '#22201b'
    paper-muted: '#5d5749'
    paper-line: '#cfc6b2'
    box-ps5: '#f3f4f6'
    box-ink: '#000000'
    box-ps4: '#003791'
    box-xbox: '#107c10'
    box-nintendo: '#e60012'
    box-pc: '#16181c'
    box-multi: '#3a3f47'
    on-cover: '#ffffff'
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
        fontFamily: 'Sofia Sans Condensed, Rubik, ui-sans-serif, sans-serif'
        fontSize: '3.75rem'
        fontWeight: 800
        lineHeight: 1.05
        letterSpacing: '-0.01em'
    headline:
        fontFamily: 'Sofia Sans Condensed, Rubik, ui-sans-serif, sans-serif'
        fontSize: '2.25rem'
        fontWeight: 800
        lineHeight: 1.05
        letterSpacing: '-0.01em'
    title:
        fontFamily: 'Rubik, ui-sans-serif, system-ui, sans-serif'
        fontSize: '1rem'
        fontWeight: 600
        lineHeight: 1.375
    body:
        fontFamily: 'Rubik, ui-sans-serif, system-ui, sans-serif'
        fontSize: '1rem'
        fontWeight: 400
        lineHeight: 1.5
    label:
        fontFamily: 'Sofia Sans Condensed, Rubik, ui-sans-serif, sans-serif'
        fontSize: '1rem'
        fontWeight: 800
        lineHeight: 1
        letterSpacing: '0.025em'
    price:
        fontFamily: 'Sofia Sans Condensed, Rubik, ui-sans-serif, sans-serif'
        fontSize: '1.5rem'
        fontWeight: 800
        lineHeight: 1
        fontFeature: 'tnum'
    menu:
        fontFamily: 'Rubik, ui-sans-serif, system-ui, sans-serif'
        fontSize: '1rem'
        fontWeight: 700
        lineHeight: 1.5
    receipt:
        fontFamily: 'JetBrains Mono, ui-monospace, monospace'
        fontSize: '0.875rem'
        fontWeight: 400
        lineHeight: 1.5
    marker:
        fontFamily: 'Permanent Marker, Sofia Sans Condensed, cursive'
        fontSize: '1.5rem'
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
    card-gap-x: '20px'
    card-gap-y: '40px'
    section: '64px'
    section-md: '80px'
    container: '80rem'
components:
    button-primary:
        backgroundColor: '{colors.accent}'
        textColor: '{colors.accent-fg}'
        typography: '{typography.label}'
        rounded: '{rounded.control}'
        padding: '0 16px'
        height: '44px'
    button-primary-hover:
        backgroundColor: '{colors.accent-hover}'
    button-secondary:
        backgroundColor: '{colors.surface-2}'
        textColor: '{colors.fg}'
        typography: '{typography.label}'
        rounded: '{rounded.control}'
        padding: '0 16px'
        height: '44px'
    button-secondary-hover:
        backgroundColor: '{colors.surface}'
    button-ghost:
        textColor: '{colors.fg-muted}'
        typography: '{typography.label}'
        rounded: '{rounded.control}'
        padding: '0 16px'
        height: '44px'
    button-ghost-hover:
        textColor: '{colors.fg}'
    input-text:
        backgroundColor: '{colors.surface}'
        textColor: '{colors.fg}'
        rounded: '{rounded.control}'
        padding: '0 12px'
        height: '44px'
    brand-section:
        backgroundColor: '{colors.brand}'
        textColor: '{colors.on-cover}'
    menu-bar:
        backgroundColor: '{colors.brand-strong}'
        textColor: '{colors.on-cover}'
        typography: '{typography.menu}'
        height: '52px'
    menu-item-current:
        backgroundColor: '{colors.accent}'
        textColor: '{colors.accent-fg}'
        padding: '0 20px'
        height: '52px'
    footer:
        backgroundColor: '{colors.brand-deep}'
        textColor: '{colors.on-cover}'
    product-card-price:
        textColor: '{colors.fg}'
        typography: '{typography.price}'
    price-sticker:
        backgroundColor: '{colors.accent}'
        textColor: '{colors.accent-fg}'
        rounded: '{rounded.control}'
        padding: '4px 8px'
    price-sticker-out-of-stock:
        backgroundColor: '{colors.surface-2}'
        textColor: '{colors.fg-muted}'
    product-cover:
        backgroundColor: '{colors.surface-2}'
        textColor: '{colors.on-cover}'
        rounded: '{rounded.card}'
    display-case:
        backgroundColor: '{colors.bg-subtle}'
        textColor: '{colors.fg}'
        rounded: '{rounded.card}'
    panel-card:
        backgroundColor: '{colors.bg-subtle}'
        textColor: '{colors.fg}'
        rounded: '{rounded.card}'
        padding: '24px'
    thermal-receipt:
        backgroundColor: '{colors.paper}'
        textColor: '{colors.paper-ink}'
        typography: '{typography.receipt}'
        padding: '28px 24px'
    error-sign:
        backgroundColor: '{colors.board}'
        textColor: '{colors.board-red}'
        typography: '{typography.marker}'
        rounded: '{rounded.control}'
        padding: '8px 16px'
---

# Design System: NextKeys

## Overview

**Creative North Star: "The Key Store in Daylight"**

The storefront is a bright, well-signed key store: a white floor with navy print, turquoise signage that frames the shop (the header with its menu bar, the banner slider, the benefits band and the footer), and one warm coral that says "do this" (the primary action, the cart count, the current menu item, prices on stickers). Everything else is quiet: soft blue-grey panels, hairline borders, and the game covers carrying the color.

Density is that of a working key store: one strict grid of product cards, each with its cover on top, the title, type and region, where it is activated, and the price with "Agregar" at the foot. Every cover wears its platform's retail band, so a listing reads as PS5, Xbox, Switch or Steam at a glance. Real cover art comes from IGDB; when a product has none, the system prints a typographic cover on a deep tint. Motion is soft and physical: covers tilt toward the pointer, buttons press to 0.97, lists rise in with a short stagger, and everything falls back to opacity and color under `prefers-reduced-motion`.

Tokens are named by role (`bg`, `surface`, `fg`, `brand`, `accent`…), not by value. A future dark theme only redefines their values under `:root[data-theme='dark']`; the turquoise sections already use this mechanism through `.surface-brand`. Two old materials survive where they still do a job: the thermal receipt for money summaries and the whiteboard sign on the error page.

**Key Characteristics:**

- White ground, navy text; turquoise brand sections at the top and bottom of the page.
- Coral marks the one primary action, the cart and the current menu item; text on it is white and bold.
- Every cover carries an official platform band; PC games show the store where they are activated.
- Condensed extra-bold caps for headings, buttons, prices and covers; Rubik for reading.
- Thermal paper and monospace for every money summary.
- Colors only through role tokens; a test rejects raw colors.

## Colors

A light, cool palette: white and blue-grey neutrals, one turquoise brand family for signage, and one coral accent for action.

### Primary

- **Harbor Turquoise** (brand): the ground of every brand section (header, banner, benefits, footer), focus rings, focused input borders, in-text links, caret, native accent color. It is the user's #29a0be darkened so white text reaches 5.3:1. **Deep Turquoise** (brand-strong) is the menu bar under the header; **Night Turquoise** (brand-deep) the footer; **Turquoise Mist** (brand-soft) the text selection.

### Secondary

- **Coral** (accent): the primary button, price stickers, the cart count when the cart has items, the current or hovered menu item, the active shelf tab underline and banner dot. Hover deepens to **Dark Coral** (accent-hover); text on it is white (accent-fg), always bold: 4.1:1 on Coral, 5:1 on Dark Coral. The coral was darkened from `#ff6b4a` on 2026-11-21 because the user disliked the dark ink on it.

### Tertiary

- **Platform bands** (box-ps5 with box-ink, box-ps4, box-xbox, box-nintendo, box-pc, box-multi): the top band of each platform's retail box, on covers only. **On Cover** (on-cover) is the light text on covers and colored bands in any theme.
- **Cover Tints** (cover-1 to cover-6: oxblood, harbor blue, felt green, tobacco, cartridge violet, deep teal): fills of generated covers, picked deterministically from the product slug.
- **Thermal Paper** (paper) with **Receipt Ink** (paper-ink), **Faded Print** (paper-muted) and **Dashed Rule** (paper-line): the receipt surface only.
- **Whiteboard** (board) with **Marker Red** (board-red), plus board-ink, board-muted and board-line: the error page's sign only.

### Neutral

- **White Floor** (bg) and **White Surface** (surface): the page and cards, inputs.
- **Mist** (bg-subtle): account and filter panels. **Mist Raised** (surface-2): secondary buttons, cover placeholders, the out-of-stock sticker.
- **Hairline** (line) and **Steel Line** (line-strong): dividers and control borders.
- **Navy Ink** (fg, 16:1 on white): primary text. **Slate** (fg-muted, 8:1) for secondary text, **Faint Slate** (fg-faint, 5.4:1) for labels, "Se activa en…", placeholders.
- **Status**: **Error Red** (danger) on **Blush** (danger-soft); **Success Green** (ok) on **Mint** (ok-soft). Used as a 50% border plus soft fill with navy text, never as large solid areas.

### Named Rules

**The Role Token Rule.** Components use role tokens only; `test/unit/design-tokens.test.ts` fails on hex, `rgb()`, arbitrary color classes and color utilities without a token in `@theme`. A new color is a new role in `main.css`, never a value in a `.vue` file.

**The Brand Surface Rule.** Inside a turquoise section, wrap it in `.surface-brand` and keep using `bg`, `fg`, `line`, `brand`: the class redefines them (white text, translucent white surfaces and lines, `brand` turns white). Never hand-pick white-on-turquoise utilities.

**The One Coral Rule.** Coral fills the single primary action of a view, prices on stickers, the cart count and the current menu item. A second coral button on the same screen means one of them is secondary.

**The Material Rule.** Paper belongs to receipts, the board to the error sign, cover tints and platform bands to covers.

## Typography

**Display Font:** Sofia Sans Condensed at 800 (with Rubik, ui-sans-serif); replaced Barlow Condensed (Saira Condensed was tried and rejected)
**Body Font:** Rubik, variable 400 to 700 (with ui-sans-serif, system-ui); replaced Barlow
**Label/Mono Font:** JetBrains Mono (with ui-monospace) for receipts, codes and order references; Permanent Marker only on the error page's sign.

**Character:** Bold store signage over a friendly sans. The condensed extra-bold caps read like the lettering on game boxes and price tags; Rubik keeps product facts plain and easy at small sizes; the monospace is the receipt printer.

All fonts are self-hosted woff2 (latin and latin-ext) in `app/assets/css/fonts.css`.

### Hierarchy

- **Display** (800, 3rem mobile to 3.75rem desktop, up to 4.5rem on the product page, line-height 1.05, uppercase): one page title per view.
- **Headline** (800, 1.875rem to 3.75rem, line-height 1.05, uppercase): section headings, the display-case heading, banner slides.
- **Title** (Rubik 600, 1rem, line-height 1.375, sentence case): product names on cards and cart lines.
- **Body** (Rubik 400, 1rem, line-height 1.5, pretty wrapping): descriptions and notes; card facts drop to 0.875rem.
- **Label** (Sofia Sans Condensed 800, 0.875 to 1.125rem, tracking 0.025em, uppercase): buttons, shelf tabs, the cart key, footer column heads.
- **Price** (Sofia Sans Condensed 800, 1.5rem on cards, 1.125 to 1.875rem on stickers, tabular): every CLP figure outside the receipt.
- **Menu** (Rubik 700, 1rem, uppercase): the menu bar items.
- **Receipt** (JetBrains Mono 400, 0.875rem; header line 0.75rem with 0.2em tracking, uppercase): everything printed on paper, order references and key codes.
- **Marker** (Permanent Marker 400, 1.5rem): the error page's sign only.

### Named Rules

**The Tabular Price Rule.** Every price, count, countdown and reference uses tabular figures (`data`, `time` and `.tabular` get them by default) and comes from the API in CLP.

**The Marker Stays on the Sign Rule.** Permanent Marker appears only on the error page's whiteboard sign.

## Layout

One centered container (80rem max) with a 16px side gutter at every width. The page opens with the turquoise header (logo, search, account, cart) and the deep-turquoise menu bar, and closes with the turquoise newsletter band and the night-turquoise footer. The home stacks its zones with 64px between them (80px from `md`): the banner slider, the tabbed shelf at full container width (no side menu), a full-width turquoise band with the platform tiles and type links (its background spans the viewport, its content stays in the container), then the turquoise benefits band.

Listings use the card grid: 2 columns, 3 from `sm`, 4 from `xl`, with 20px between columns and 40px between rows. Card facts stack in fixed order (title, type · region, "Se activa en …"), and the price row is pushed to the card's foot so prices align across a row.

Money pages pair a working column with a receipt: the cart lines on the left, the receipt on the right from `lg`, stacking below on small screens. Account pages are one narrow column (28rem) with the form on a Mist panel.

Breakpoints are Tailwind's defaults (`sm` 640px, `md` 768px, `lg` 1024px, `xl` 1280px). Below `md` the menu bar collapses into a drawer that slides in from the left; the search moves to its own row under the logo; catalog filters fold into a disclosure that stays open from `lg`.

## Elevation & Depth

Mostly flat. Surfaces are tonal steps (white, Mist, Mist Raised, turquoise) separated by hairlines; shadows are soft, navy-tinted and belong to objects you could pick up: covers, stickers, the primary button, the receipt.

### Shadow Vocabulary

- **Case** (`0 18px 36px -22px rgb(11 31 51 / 0.45), 0 2px 6px rgb(11 31 51 / 0.12)`): covers at rest, the fanned banner covers.
- **Lift** (`0 18px 32px -14px rgb(11 31 51 / 0.45), 0 4px 10px rgb(11 31 51 / 0.12)`): a cover tilted on hover.
- **Tag** (`0 3px 8px -2px rgb(11 31 51 / 0.35)`): price stickers, the primary button and the error sign.
- **Receipt** (`0 12px 28px -12px rgb(11 31 51 / 0.35)`): the receipt slip in the banner; the `.receipt` class itself uses `drop-shadow(0 8px 16px rgb(11 31 51 / 0.22))` as a filter, so it follows the torn edge.

### Named Rules

**The Shadow Follows the Object Rule.** Shadows belong to covers, stickers, buttons and receipts. Panels, bands, nav bars and form sections have borders or tonal steps, not shadows.

## Shapes

Small, practical corners: 4px for controls (buttons, inputs, stickers, the cart pill) and 6px for cards, covers, the banner, the benefits band and panels. Borders are 1px hairlines; dashed borders mark paper-like or provisional things (receipt rules, the empty state). Prices are never rotated; the error sign is rotated -2deg. The receipt is clipped with an 8px zig-zag tear at top and bottom.

## Components

### Buttons

Bold store keys: condensed extra-bold caps, solid and pressable (`app/components/ui/Button.vue`).

- **Shape:** gently squared (4px).
- **Primary:** Coral with white bold text and the tag shadow; hover Dark Coral. Heights 36, 44 and 52px with 12, 16 and 24px side padding.
- **Secondary:** Mist Raised with a Steel Line border and Navy Ink text; on hover the fill turns white and the border Faint Slate.
- **Ghost:** bare Slate label that darkens to Navy Ink ("Ver todo").
- **Press / Focus:** every pressable scales to 0.97 over 160ms on the out curve; focus is a 2px turquoise outline with 2px offset (white inside brand sections). Disabled is 50% opacity with a not-allowed cursor.
- **Icon + label:** inline SVG icons at 16 to 22px; the card's add button shows only the cart icon on mobile and "Agregar" from `sm`.

### Product Card

A key-store card (`ProductCard.vue`): the cover (3:4, tilting) with its platform band, the title in Rubik 600 (the title link covers the whole card; hover turns it turquoise), "type · region" in Slate, "Se activa en X" in Faint Slate (hidden when the product has no activation service), and at the foot the price in display 800 at 1.5rem with a primary "Agregar" button that sits above the card link. Out of stock adds a small "Sin stock" under the price. No station numbers and no sticker on cards.

### Product Cover

A cover at 3:4 with 6px corners and a platform band on top, like the platform's retail box; there is no frame around the art. Marks are official and in one ink: PS5 is a white band with a black mark; PS4 blue; Xbox green with the sphere, wordmark and generation in condensed caps; Nintendo red with the Switch or Switch 2 lockup. PC and Mac games show their activation store instead of "PC": a near-black band with the store mark and name (Steam; Epic Games, EA, GOG, Ubisoft and Battle.net are ready), the service name when it has no mark. An Xbox Play Anywhere key shows only Xbox. Programs (licenses) name their operating systems ("Windows · macOS"). Several families share the graphite band with one entry per family, split by a hairline. Family and tone come from `platformBox()` (`app/utils/platform-box.ts`); the marks live in `app/utils/platform-marks.ts` with their sources (Simple Icons CC0, public-domain Wikimedia Commons files). The band is labeled "Plataformas: …" for screen readers.

Inside, the real cover art (imported from IGDB with `catalog:import-covers`, or uploaded in the panel) fills the front; otherwise a generated front prints the title in condensed caps at the foot and type and region in small caps, on a cover tint. With `cover_band` off (an API flag) an uploaded cover shows bare.

### Cover Tilt

Wraps a cover; on fine hover pointers without reduced motion it rotates up to 7deg (9deg for the lead cover) in 900px perspective, rises 4px, swaps the case shadow for lift and slides a soft white glare under the pointer. It returns over 260ms. On touch or reduced motion the cover only changes shadow.

### Price Sticker

A coral CLP label stuck on a box (`PriceSticker.vue`), used only on the deal of the day: display 800, tabular, flat (never rotated), with the tag shadow. The product page shows its price as large display text in the details column, not as a sticker. Out of stock it turns Mist Raised with Slate text and a "Sin stock" line. There are no strike-through or "before" prices.

### Banner slider

Home only, first zone: a turquoise brand section (6px corners) with three slides on a scroll-snap track: the deal of the day (monospace countdown to Santiago midnight, the price sticker, a tilted cover), "Paga con Webpay" with a thermal receipt slip, and "Tu clave, al instante" with three fanned covers. Dots (the active one coral), pause and arrow buttons (translucent white keys that turn white-bordered on hover) sit on the bottom edge. It advances every 7s, pauses on hover, focus and its pause button, never moves on its own under reduced motion, and off-screen slides are `inert`.

### Platform Band

Home only, right after the shelf: a turquoise brand section that spans the viewport width, its content in the container. Four platform tiles drawn by hand in SVG (`CategoryTile.vue`), one recipe for all: the brand gradient (PlayStation blue, Xbox green, Nintendo red, Steam slate), a faint pattern of its own (the four PlayStation shapes, rings, Joy-Con dots, diagonal rules), the official symbol at the top left, a large ghost of it off the bottom right corner, and the name in display caps over its platforms. No store artwork. PC is Steam, a store filter (`activation_service=steam`), because PC games are activated there (as the cover band says); PC programs are reached through Licencias, then the three product types as smaller tiles of the same recipe, tied to no brand: Claves digitales in coral over key codes with a key, Licencias in Windows blue with the four panes and four hand-drawn app icons on white keys (a document, a spreadsheet, an antivirus shield, a cloud; generic glyphs, no product logos) in a row at the top, beside the arrow, so the title and subtitle keep the full width (`art="above"`), Juegos físicos in plum over discs with three real physical covers fanned from the bottom right (from the API, one per title; the game case ghost when there are none). Hovering a tile only grows its ghost symbol 10% from its corner; arrows never move. It replaced the display case and the shelf's category side menu on 2026-11-21.

### Thermal Receipt

Cream paper, monospace print, torn zig-zag edges, dashed rules between blocks and a centered tracked header ("NEXTKEYS · DETALLE"). It holds the cart summary, the checkout total, the payment result and the order detail. The primary action may sit on the paper.

### Inputs / Fields

- **Style:** 44px tall, white fill, Steel Line 1px border, 4px corners, 12px side padding, Navy Ink text, Faint Slate placeholder. Set once for text, email and password fields in the base layer. Inside brand sections (search, newsletter) the field is translucent white with a white hairline.
- **Focus:** the border turns turquoise (no extra outline). Checkboxes and native selects take the turquoise accent color.
- **Error / Status:** a 6px panel with a 50% Error Red border on Blush; confirmations the same in Success Green on Mint.

### Navigation

The header is a turquoise brand section: logo, search field, account links in muted white, and the cart as a secondary key whose count pill turns coral once the cart has items. Under it, from `md`, the menu bar follows Koparion's main menu: a Deep Turquoise strip (52px) with Inicio, Catálogo, the platform families and the product types in bold uppercase Rubik, 20px side padding and a 2px gap so two filled items never touch, horizontally scrollable. The current page (`aria-current`) and the hovered item (fine pointers only) fill the full bar height in coral with white text, like a button; the fill fades over 150ms. Styles live in `.menu-tab` (`main.css`). Shelf tabs use condensed caps with a 2px coral underline on the active one. On small screens a drawer slides in from the left (200ms out, 280ms in on a drawer curve).

### Footer

A turquoise newsletter band, then the Night Turquoise footer with link columns (condensed caps heads in faint white, links in muted white that turn white on hover).

### Error sign

Error page only: a whiteboard sign in Permanent Marker, Marker Red on board, rotated -2deg with the tag shadow ("Fuera de servicio").

### Empty State

A dashed Steel Line box with a condensed caps message and one way forward.

## Do's and Don'ts

### Do:

- **Do** keep the page on White Floor (#ffffff) with Navy Ink text, and put turquoise only on the brand sections (header, menu bar, banner, benefits, footer).
- **Do** wrap every turquoise section in `.surface-brand` and keep using role tokens inside it.
- **Do** add new colors as role tokens in `app/assets/css/main.css`; a future dark theme redefines values under `:root[data-theme='dark']`.
- **Do** put every price in display 800 with tabular figures, in CLP from the API.
- **Do** give every cover its platform band, and show the activation store (not "PC") for PC and Mac games.
- **Do** print every money summary (cart, checkout, result, order) on the thermal receipt in JetBrains Mono.
- **Do** give pressable elements the 0.97 press and reduce all motion to opacity and color under `prefers-reduced-motion`.

### Don't:

- **Don't** write raw colors (hex, `rgb()`, `bg-[#…]`) in `.vue` files, or color utilities without a token; the design-tokens test fails.
- **Don't** use more than one coral action per view, or regular-weight text on coral (white text there must be bold).
- **Don't** add a frame around cover art; the band is the only mark.
- **Don't** put station numbers or price stickers on listing cards.
- **Don't** use Permanent Marker outside the error sign, or paper and board colors outside their objects.
- **Don't** show "before" prices, invented discounts, ratings or reviews.
- **Don't** put shadows on panels, bands, nav bars or form sections; use borders and tonal steps.
