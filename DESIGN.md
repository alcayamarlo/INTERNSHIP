# SkillBridge design system

One look for every role (Student, Coordinator, Employer, Administrator). Only content and nav items differ.
There is no Supervisor role in the codebase (`App\Enums\UserRole` has Admin, Student, Employer, Coordinator).

## 1. Tokens: `public/css/sb-theme.css`

This is the single source of truth. It loads last in `resources/views/layouts/app.blade.php`.

| Group | Tokens |
|---|---|
| Brand | `--brand-600` primary, `--brand-700` hover, `--brand-500` focus, `--brand-300` soft/hover border, `--brand-100` chip tint, `--brand-maroon`, `--brand-gradient` |
| Surfaces | `--bg-app`, `--bg-sidebar`, `--bg-card`, `--bg-card-muted`, `--border`, `--border-strong` |
| Text | `--text-primary`, `--text-secondary`, `--text-muted`, `--brand-text` (red used as text, AA in both themes) |
| Status | `--success-*`, `--warning-*`, `--danger-*`, `--neutral-*` (each `-bg` and `-text`) |
| Misc | `--shadow-card`, `--chart-grid`, `--chart-secondary-line`, `--chip-bg`, `--card-hover-border`, `--active-glow` |

**Change the brand colour:** edit the `--brand-*` values in the first block of `sb-theme.css`. Nothing else.

Light is the default; `:root[data-theme="dark"]` overrides the surface, text and status tokens.
The old `--sb-*` and `--ad-*` variables that existing components read are mapped onto these tokens in `sb-theme.css` (section 1), so legacy markup follows the theme automatically.

**Rule:** components never contain hex values. Use `var(--token)`.

## 2. Theme switching

- `<html data-theme="light|dark">`, set before first paint by `resources/views/partials/theme-init.blade.php` (saved choice, else `prefers-color-scheme` in the app layout).
- The sun/moon button in the top bar has `data-theme-toggle`; `public/js/skillbridge-ui.js` handles clicks, icon, `aria-pressed` and localStorage (`skillbridge-theme`).

## 3. Shell (`layouts/app.blade.php`)

One shell for all roles: sidebar (logo, section labels, icon nav, solid red active pill, gradient promo card), top bar (workspace pill, search, date pill, theme toggle, messages, bell, user menu). Per-role differences are only the `$navGroups` array, the search placeholder and the promo card content.

## 4. Shared components (`resources/views/components`)

| Component | Use |
|---|---|
| `<x-page-header title subtitle eyebrow>` | eyebrow, title, subtitle; slot = right-aligned chips/actions |
| `<x-dashboard-stat-card title value icon description :show-progress :href>` | KPI card |
| `<x-section-card title subtitle icon>` (+ `<x-slot:actions>`) | card with header |
| `<x-tabs :tabs="['progress'=>['Progress','bi-bar-chart']]">` | pill tabs, `?tab=` in the URL, plain links |
| `<x-chip tone="success\|warning\|danger\|neutral\|brand">` | status chip |
| `<x-status-badge>` | status chip driven by a status key |
| `<x-activity-item icon title time tag tone>` | icon chip + title + time + tag |
| `<x-empty-state icon title message>` | empty state |
| `<x-dashboard-quick-actions>`, `<x-dashboard-ojt>`, `<x-ojt-progress-card>` | dashboard widgets |

Plain Bootstrap classes (`.card`, `.table`, `.btn-primary`, `.btn-outline-secondary`, `.form-control`, `.alert-*`, `.badge`, `.modal`, `.nav-pills`) are themed globally in `sb-theme.css`, so they match without extra classes. `.sb-skeleton` is the loading placeholder.

Status colours everywhere: Ongoing / Completed = success, Pending = warning, At risk / Rejected = danger, Draft = neutral.

## 5. Building a new page

```blade
@extends('layouts.app')
@section('title', 'My page')
@section('content')
<x-page-header title="My page" subtitle="What it does" eyebrow="My workspace">
    <x-chip tone="success">Ongoing</x-chip>
</x-page-header>

<div class="sb-stat-grid">           {{-- 4 KPI cards --}}
    <x-dashboard-stat-card title="Total" :value="$total" icon="bi-people" />
    ...
</div>

<div class="sb-layout-2">             {{-- 2/3 + 1/3 --}}
    <x-section-card title="Main" subtitle="Optional">...</x-section-card>
    <x-section-card title="Recent activity">
        <x-activity-item icon="bi-bell" title="Something happened" time="2h ago" tag="New" tone="brand" />
    </x-section-card>
</div>
@endsection
```

Page-specific CSS goes in its own file that only uses tokens (`var(--bg-card)`, `var(--border)`, `var(--brand-600)`...).

## 6. Layers

`skillbridge-ui.css` (base components) → `skillbridge-dashboard.css` / `skillbridge-sidebar.css` (legacy structure) → `sb-eye-comfort.css` (shared with public pages) → role CSS → `sb-responsive.css` → **`sb-theme.css` (wins)**.
Selectors in `sb-theme.css` use a `html:root:root:root:root` prefix to out-rank the older layers; keep it when adding rules there.
