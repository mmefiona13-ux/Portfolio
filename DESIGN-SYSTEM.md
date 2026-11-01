# Collins M design system

Figma was not connected in this environment, so this document is the implementation reference for a Figma file. The live implementation is in `includes/header.php`, `assets/css/site.css`, `assets/css/ecommerce.css`, and `admin/customize.php`.

## Brand tokens

- Primary: `#1677ff` (admin configurable); deep navy: `#07111f`; ink: `#0a1525`.
- Surface: `#ffffff`; page background: `#f6f8fb`; muted text: `#687386`; divider: `#e3e7ed`.
- Success: `#159447`; WhatsApp: `#25d366`; sale: `#dc2626`.
- Display and UI headings: Manrope, 500–800. Body and controls: DM Sans, 400–700.
- Content width: 1180px maximum, 20px desktop gutters and 16px mobile gutters.

## Components

- Navigation: 76px sticky desktop header; collapsible mobile menu below 680px.
- Primary action: blue filled button; secondary: navy filled or outlined/glass button; WhatsApp action: green.
- Product card: image panel, brand badge, name, storage/condition, price, inventory state and actions. Cards lift subtly on hover.
- Forms: white controls with 1px neutral border, 6px radius, visible keyboard focus, labels above fields.
- Status: green in-stock, red out-of-stock, amber BNPL/finance disclosure, red flash-sale badge.
- Responsive catalogue: 4 columns at desktop, 2 columns below 900px, 1 column below 390px.

## Desktop and mobile references

- Desktop homepage: full-width image hero, promotional banner, compact recent-view strip, time-bounded sale cards, responsive filter form and 4-column catalogue.
- Mobile homepage: stacked hero actions, horizontally scrollable recent products, single-column filters and two-column product cards until 390px, then one column.
- Product detail: 2-column image/specification layout on desktop; stacked gallery, purchase controls and related products on mobile.
- Account/admin: two-column account panels on desktop, single column on mobile; admin tables remain data-oriented and may scroll horizontally on narrow screens.

## Motion and accessibility

Use short hover transitions. Respect `prefers-reduced-motion`; keep focus outlines visible; announce catalogue count changes with a live region; use text labels alongside status colour. The existing phone store artwork and icon set remain in place.
