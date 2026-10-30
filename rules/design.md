# Mentora UI Design Rules

Canonical design system for Mentora’s Vue/Inertia admin UI.  
Tokens live in `resources/css/app.css`. Shared components live in `resources/js/Components/UI/`.  
Page patterns are also summarized in `docs/frontend-page-conventions.md`. Preview components at `/dev/ui` (local/debug only).

---

## 1. Design principles

- **Admin LMS, not marketing.** Dense, calm, functional layouts.
- **One system.** Prefer shared `App*` components over one-off markup.
- **Token-first.** Use CSS theme tokens / Tailwind semantic classes (`bg-surface`, `text-muted`, `border-border`, `primary-*`) instead of hard-coded hex where possible.
- **Restrained brand.** Blue primary accent; avoid purple gradients, heavy glow, or decorative hero layouts inside the app shell.
- **Honest empty states.** Never invent school data or metrics the API did not return.
- **Accessible by default.** Visible focus rings; respect `prefers-reduced-motion`.

---

## 2. Typography

| Token / rule | Value |
|--------------|--------|
| Font family | **Instrument Sans** (`--font-sans`), with system fallbacks |
| Base size | `text-sm` on `body` |
| Leading | `leading-normal` |
| Rendering | `antialiased` |
| Page titles | Use `PageHeader` (typically stronger weight via header component) |
| Section titles in cards | `text-base font-semibold text-text` |

Do not introduce Inter/Roboto/Arial as the primary UI font unless replacing the design system intentionally.

---

## 3. Color tokens

Defined under `@theme` in `resources/css/app.css`.

### Brand (primary)

Restrained blue scale: `primary-50` … `primary-950`.  
Primary actions: `bg-primary-600` / hover `bg-primary-700`.

### Surfaces & text

| Token | Role |
|-------|------|
| `canvas` | Page background (`#f1f5f9`) |
| `surface` | Cards, panels, inputs (`#ffffff`) |
| `surface-muted` | Subtle fills / disabled backgrounds (`#f8fafc`) |
| `border` | Default borders (`#e2e8f0`) |
| `border-strong` | Stronger dividers (`#cbd5e1`) |
| `text` | Primary text (`#0f172a`) |
| `text-secondary` | Secondary text (`#334155`) |
| `text-muted` | Hints, labels, meta (`#64748b`) |

### Semantic

Use for badges, alerts, and status — not as page themes:

- **Success:** `success-50/100/600/700`
- **Warning:** `warning-50/100/600/700`
- **Danger:** `danger-50/100/600/700`
- **Info:** `info-50/100/600/700` (aligned with primary blue)

---

## 4. Radius, shadow, spacing

| Token | Value | Use |
|-------|--------|-----|
| `--radius-control` | `0.5rem` | Inputs, buttons, selects, nav items |
| `--radius-card` | `0.75rem` | Cards, large panels |
| `--shadow-card` | Soft 1px elevation | Cards |
| `--shadow-overlay` | Stronger elevation | Modals / overlays |

**Layout tokens**

| Token | Value |
|-------|--------|
| `--sidebar-width` | `16rem` |
| `--sidebar-collapsed-width` | `4.5rem` |
| `--header-height` | `3.75rem` |

**Content width:** main content sits in `max-w-7xl` with horizontal padding (`px-4` → `lg:px-8`).

Keep shadows minimal. Prefer border + light shadow over multi-layer “glow” stacks.

---

## 5. Focus & motion

- `:focus-visible` → `2px` outline using `primary-600`, `outline-offset: 2px`.
- Interactive controls also use `focus-visible:ring-2 focus-visible:ring-primary-600`.
- Under `prefers-reduced-motion: reduce`, animations/transitions are effectively disabled.

---

## 6. Shared components (required kit)

Prefer these over raw HTML for product UI:

| Component | Purpose |
|-----------|---------|
| `AppButton` | Actions / links |
| `AppInput` | Text fields |
| `AppSelect` | Selects |
| `AppTextarea` | Multiline text |
| `AppCheckbox` | Checkboxes |
| `AppCard` | Content containers |
| `AppTable` | Data tables |
| `AppPagination` | Laravel paginator UI |
| `AppBadge` | Status chips |
| `AppAlert` | Inline banners |
| `AppModal` | Dialogs |
| `AppSkeleton` | Loading placeholders |
| `EmptyState` | No-data views |
| `ErrorState` | Recoverable errors |
| `PageHeader` | Title, description, breadcrumbs, actions |
| `ConfirmDialog` | Destructive confirms |
| Toasts (`useToast`) | Flash / ephemeral feedback |

### AppButton

- **Variants:** `primary` \| `secondary` \| `outline` \| `ghost` \| `danger`
- **Sizes:** `sm` \| `md` \| `lg` \| `icon`
- Support `loading`, `disabled`, `href` (Inertia `Link`), `block`
- Height targets: sm `h-8`, md `h-10`, lg `h-11`

### AppBadge

- **Variants:** `neutral` \| `primary` \| `success` \| `warning` \| `danger` \| `info`
- Pill shape (`rounded-full`), compact `text-xs`

### AppCard

- `rounded-[var(--radius-card)]`, `border-border`, `bg-surface`, `shadow-card`
- Optional `header` / `footer` slots with divider borders
- Default padding `p-5` (`padded` prop)

### Form controls

- Height ~`h-10`, `text-sm`, `rounded-[var(--radius-control)]`
- Focus: `border-primary-500` + ring `primary-600/20`
- Disabled: muted surface + reduced opacity
- Always pass Laravel/Inertia errors via `error` / `form.errors.field`

---

## 7. App shell & navigation

- Authenticated pages use **`AppLayout`** (sidebar + header + main).
- Page chrome title/actions via **`PageHeader`** with breadcrumbs.
- Sidebar items come from `resources/js/Config/navigation.js` and must reference **real named routes**; broken links are filtered out.
- Icons: Lucide (`@lucide/vue`).
- Flash messages → toast container; optional banner → `AppAlert`.

---

## 8. Page patterns

### Index

1. `PageHeader` with title, short description, primary **Create** (if permitted).
2. Search/filters via Inertia `router.get` with `preserveState` / `preserveScroll`.
3. `AppTable` + `AppPagination` from Laravel paginator props.
4. `EmptyState` when the list is empty.

### Create / Edit

1. `useForm()` from `@inertiajs/vue3`.
2. Map validation errors to control `error` props.
3. Disable submit while `form.processing`.
4. Prefill Edit from server props; do not invent fields the API does not provide.
5. Prefer a shared `Form.vue` partial for Create/Edit when fields match.

### Show

1. Summary `AppCard` for primary attributes.
2. Related lists only when the backend includes them.
3. Header actions for Edit / destructive flows when permitted.

### Module dashboard

1. Small set of **server-provided** summary cards.
2. Honest empty/zero states — never fabricate figures.

### Settings

1. Group forms by section.
2. Match submit behavior to the controller contract (one submit per group or one shared form).

---

## 9. Permissions & honesty

- UI visibility may use `usePermissions()` / shared auth props.
- Backend authorization remains the source of truth.
- Hide unavailable actions; do not show dead buttons for missing routes.

---

## 10. Do / Don’t

### Do

- Use design tokens and `App*` components.
- Keep spacing consistent (`gap-4`, card `p-5`, section `space-y-6`).
- Use semantic badge/alert colors for status.
- Keep tables, forms, and headers visually quiet and scannable.

### Don’t

- Build purple-on-white / indigo glow “AI default” themes inside the app.
- Use heavy multi-shadow cards or full-bleed marketing heroes in admin screens.
- Hard-code random hex colors when a token exists.
- Duplicate form/table markup instead of shared components.
- Fabricate stats, empty-list placeholders that look like real data, or nav links without routes.

---

## 11. File map

| Concern | Location |
|---------|----------|
| Tokens | `resources/css/app.css` |
| UI components | `resources/js/Components/UI/` |
| Navigation / page header | `resources/js/Components/Navigation/` |
| Layout shell | `resources/js/Layouts/AppLayout.vue` |
| Nav config | `resources/js/Config/navigation.js` |
| Page conventions | `docs/frontend-page-conventions.md` |
| Live showcase | `resources/js/Pages/Dev/UiShowcase.vue` → `/dev/ui` |