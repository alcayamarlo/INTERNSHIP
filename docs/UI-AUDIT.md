# SkillBridge UI audit (Phase 0, read-only)

Scope: Blade views and CSS only. Nothing under `routes/`, `app/`, `database/` was read for change.

## 1. Layout and partials
| Area | Files |
|---|---|
| One app shell for all 4 roles | `resources/views/layouts/app.blade.php` (sidebar `.sb-sidebar`, topbar `.sb-topbar`, content `.sb-content`, global `sbConfirmModal`, nav scroll restore) |
| Public / auth | `layouts/public.blade.php`, `layouts/guest.blade.php`, `welcome.blade.php`, `partials/auth-modal.blade.php` (Sign-in modal, fixed light palette) |
| Shared components | `components/`: page-header, section-card, status-badge, verified-badge, empty-state, table-filters, tabs, avatar, chip, breadcrumbs, dashboard-stat-card, dashboard-quick-actions, dashboard-announcements, dashboard-ojt, ojt-progress-card, activity-item, resume-preview, **modal** (new) |
| Shared partials | `partials/report-modal.blade.php`, `report-button`, `theme-init`, `public-theme-head` |

Only 32 pages use the `.sb-dash` wrapper; the student Portfolio page used its own `student-page-shell` (now migrated to `x-page-header`).

## 2. CSS and tokens (about 11,300 lines)
| File | Lines | Owns |
|---|---|---|
| `skillbridge-ui.css` | 4,737 | Legacy base: layout, page header, tables, dialogs, forms (oldest layer) |
| `sb-glass.css` | 1,900 | Landing page only |
| `sb-theme.css` | 1,366 | App theme layer, 196 `!important`; sidebar/topbar vars (256px, 68px) |
| `auth-card.css` | 536 | Sign-in modal (fixed palette, must not follow theme) |
| `sb-motion.css`, `sb-icons.css`, `sb-responsive.css`, `sb-nav-controls.css`, `skillbridge-sidebar.css`, `skillbridge-dashboard.css`, `sb-eye-comfort.css` | 56-381 | Motion, icons, responsive tweaks, sidebar, dashboard |
| `sb-tokens.css` | 203 | Brand tokens, light and dark (`--burgundy`, `--surface`, `--line`, `--text`, `--muted`) |
| Page files added in this project | 3-218 | `employer-applicant-review`, `applicant-side-card`, `employer-company-profile`, `employer-internship-form`, `coordinator-review-cards`, `coordinator-dashboard-columns`, `academic-calendar-layout`, `ojt-weekly-report`, `sb-modal` |

## 3. Pop-ups
- **Bootstrap modals (17 in 13 files):** admin announcements (create + per-row edit), admin users (suspend + edit per row), employer internships (new internship), employer students invite, messages forward, report content, student certificate wizard, application edit, internships preview + apply (2), internship apply, OJT daily + weekly, global `sbConfirmModal`. Sizes: 8 `modal-lg`, 5 fullscreen-below-sm.
- **Native `<dialog>` (about 10):** admin file preview, coordinator verification file preview, student change request + portfolio preview + documents, employer applicant modal + file preview, plus `x-modal` (Add program, Invite a company).
- Header/close styles: Bootstrap `modal-header` + `btn-close` (24x), `sb-dialog-header`, `applicant-modal__*`, and the new `.sb-modal__*` shell.

## 4. Pages per role
- **Admin (19):** academics, program-form, announcements, company-invitations, dashboard, moderation (index/show), placements (index/show), portfolio-files, profile, skill-tags, students import (2), system (health/logs/reports), users (index/create).
- **Coordinator (16):** analytics, applications, companies (index/invitations/show), criteria (index/show), dashboard, placements (index/show), profile, reviews, statistics, students (index/show), verification.
- **Employer (11):** applicants (index/show), dashboard, interns (index/show), internships (index/form), invitations, profile, students (index/show).
- **Student (~25):** certificates, competencies (index/show), dashboard, internships (application-edit/show, applications, index, show), invitations, ojt, portfolio (create/edit/index/show), profile + 8 tabs, resume (edit/index).

## 5. Inconsistencies
| Item | Found | Target |
|---|---|---|
| Sidebar width | 256px (`--sb-sidebar-width`) | 260px |
| Topbar height | 68px (`--sb-topbar-height`) | 72px |
| Page title | 40px (`sb-theme`), 36px, 30px mobile; custom headers on a few pages | 32-36px via `x-page-header` |
| Card/panel radius | 10/11/12/14/16/18/20/22/24px mixed | cards 20, inputs 12, pills 999 |
| Control heights | 36/40/44/46/48px mixed | 46px (44px minimum hit target) |
| Page wrapper | `.sb-dash` (32 pages) vs `student-page-shell` and bare containers | one `.sb-dash` content region |
| Tables | `table table-hover mb-0` (10), `sb-table-stack` (7), `stats-table`, bare `table-responsive` (35) | one table style, sticky header |
| Modals | 17 Bootstrap + ~10 native with 4 different shells | one `x-modal` shell |
| Hard-coded colors | many `#FFF`, `#16161A`, `#E6E1E3` in page CSS and views | tokens only |
| `!important` | 196 in `sb-theme.css` | remove at the source (e.g. `.sb-dash > *` margin rule broke dialog centering) |
| Status colors | `x-status-badge` tones (success/warning/danger/info) differ per page; verified badge now green | black success, warning/error tints, verified green exception |

## 6. Pages that force scrolling (largest first, by view size)
1. `student/competencies/index` 769 lines (wizard, list, filters stacked)
2. `employer/internships/index` 398 (table + new internship modal + form)
3. `student/internships/index` 426 and `student/dashboard` 401 (long stacked sections)
4. `admin/academics/index` 389 (3 tabs, calendar tab was reorganized)
5. `student/internships/applications` 335, `application-show` 332
6. `coordinator/students/show` 319, `admin/users/index` 313 (per-row modals), `student/invitations/index` 308, `student/ojt/index` 284
7. `student/profile/*` tabs (long forms), `employer/applicants/show` (already viewport-fit)

Typical causes: long forms in one card, stacked cards instead of tabs, tall tables with no inner scroll, big page headers, per-row modals inflating markup.

## 7. Notes
- No `CLAUDE.md` exists, so the rules come from your message.
- Tests exist (`tests/Feature`, `tests/Unit`, Pest); to be run after each phase.
- I cannot view a browser: verification is `php -l`, the Pest suite, and your visual checklist.
