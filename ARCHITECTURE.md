# Tour — Architecture (Elgg 7.x)

## Summary

**Name**: Tour
**Version**: 7.0.0 — migrated to Elgg 7.x on 2026-05-24 (from 6.x)
**Purpose**: Manage and display in-app feature tours for Elgg sites.

A site admin defines per-URL tour pages (`Tour\Page`) and a list of
ordered tour stops (`Tour\Stop`) that target DOM elements on that URL.
At runtime, an end user opens the topbar "Help" link and the
admin-selected client library (Hopscotch or Joyride) draws the tour
overlay using stop metadata fetched from the `/tour/data` AJAX endpoint.

## Directory Structure

```
tour/
├── elgg-plugin.php                   # Declarative entities/actions/routes/events/cli_commands
├── composer.json                     # Sole plugin metadata (4.x+ — no manifest.xml)
├── docker/                           # elgg7 Docker infra (per Iron Law 12)
├── classes/
│   └── Tour/
│       ├── Bootstrap.php             # extends DefaultPluginBootstrap; runtime settings-dependent regs + Seeder wiring
│       ├── Page.php                  # ElggObject subtype 'tour_page'; getURL(): string
│       ├── Stop.php                  # ElggObject subtype 'tour_stop'
│       ├── Cli/
│       │   └── DoctorCommand.php     # `tour:doctor` post-migration integrity check
│       ├── Database/
│       │   └── Seeds/
│       │       └── Seeder.php        # \Elgg\Database\Seeds\Seed subclass for tour_page + tour_stop
│       ├── Page/
│       │   ├── EntityMenu.php        # menu:entity register event (\Elgg\Event)
│       │   └── Form.php              # tour_page/save form vars
│       └── Stop/
│           ├── EntityMenu.php        # menu:entity register event (\Elgg\Event)
│           └── Form.php              # tour_stop/save form vars
├── actions/
│   ├── tour_page/
│   │   ├── save.php
│   │   ├── delete.php
│   │   └── reorder.php
│   └── tour_stop/
│       ├── save.php
│       └── delete.php
├── views/
│   └── default/
│       ├── resources/tour/data.php   # /tour/data route — JSON or HTML payload
│       ├── tour/{hopscotch,joyride}.php # render helpers consumed by data view
│       ├── object/{tour_page,tour_stop}.php
│       ├── forms/{tour_page,tour_stop}/save.php
│       ├── ajax/tour_stop/save.php
│       ├── admin/administer_utilities/tour{,/add,/edit,/view,/stop/add,/stop/edit}.php
│       ├── css/{tour,tour_admin}.php
│       ├── elgg/tour/{display,edit,reorder}.mjs  # ES modules (replaces AMD)
│       └── plugins/tour/settings.php # Hopscotch vs Joyride selector
├── languages/
│   ├── en.php
│   └── fi.php
└── vendors/
    ├── hopscotch/                    # Third-party tour library (UNTOUCHED)
    └── joyride/                      # Third-party tour library (UNTOUCHED)
```

## Entities

| Type | Subtype | Class | Capabilities |
|------|---------|-------|--------------|
| object | `tour_page` | `Tour\Page` | searchable=false, commentable=false, likable=false |
| object | `tour_stop` | `Tour\Stop` | searchable=false, commentable=false, likable=false |

Subtype constants live on the entity classes (`Tour\Page::SUBTYPE`,
`Tour\Stop::SUBTYPE`) and were preserved across migration — no DB
migration needed.

Per cross-plugin learning: the `class` entry in elgg-plugin.php
remains a **string literal** (`'Tour\\Page'`), not `\Tour\Page::class` or
`\Tour\Page::SUBTYPE`. elgg-plugin.php is parsed during plugin discovery,
before the plugin's `classes/` PSR-4 autoloader is wired in.

## Bootstrap

`Tour\Bootstrap` extends `\Elgg\DefaultPluginBootstrap` and implements
`init()` to register the runtime, settings-dependent items that cannot
live in the declarative `elgg-plugin.php` config, plus the Seeder wiring:

| Registration | Why it cannot be declarative |
|--------------|------------------------------|
| `elgg_register_external_file('css', ...)` for joyride **or** hopscotch CSS | Choice depends on `js_library` plugin setting at request time |
| `elgg_register_external_file('js', 'tour.joyride'\|'tour.hopscotch', ...)` + `elgg_load_external_file('js', ...)` | 3rd-party libraries expose a global (`window.hopscotch`, `$.fn.joyride`) and are NOT ES modules — register as plain external scripts |
| `elgg_import_esm('elgg/tour/display')` | Loaded site-wide so the topbar link works on every page; resolves to `views/default/elgg/tour/display.mjs` |
| `elgg_register_menu_item('topbar', ['name' => 'tour', ..., 'data-library' => $js_lib])` | Menu item carries the currently-selected library as a data attribute consumed by the ESM display module |
| `elgg_register_event_handler('seeds', 'database', [Seeder::class, 'addSeed'])` | Required: Seed subclasses are exposed via the `seeds` event; the closure-free `'events'` key cannot hold static method refs that reference plugin classes from `elgg-plugin.php` |

## Routes

| Name | Path | Resource view |
|------|------|---------------|
| `default:view:tour:data` | `/tour/data` | `tour/data` |

The admin UI (`admin/administer_utilities/tour/...`) is provided by
core's admin context handler — not via plugin-owned routes.

## Events

In Elgg 5.x+, hooks and events merged into a single `'events'` key with
`\Elgg\Event` callbacks. Declarative event handlers:

| Event | Type | Handler | Purpose |
|-------|------|---------|---------|
| `register` | `menu:entity` | `Tour\Page\EntityMenu::setUp` | Keep only access/edit/delete on `Tour\Page`; rewrite edit href |
| `register` | `menu:entity` | `Tour\Stop\EntityMenu::setUp` | Keep only access/edit/delete on `Tour\Stop`; rewrite edit href |

Both handlers use the 5.x+ `\Elgg\Event` single-arg signature and handle
both array and `Elgg\Menu\MenuItems` return shapes.

Runtime registration in Bootstrap:

| Event | Type | Handler | Purpose |
|-------|------|---------|---------|
| `seeds` | `database` | `Tour\Database\Seeds\Seeder::addSeed` | Append `Seeder::class` to the database seeds list |

## Actions

| Action | File | Access | Purpose |
|--------|------|--------|---------|
| `tour_page/save` | `actions/tour_page/save.php` | admin | Create or update a tour page |
| `tour_page/delete` | `actions/tour_page/delete.php` | admin | Delete a tour page |
| `tour_page/reorder` | `actions/tour_page/reorder.php` | admin | Reorder tour stops within a page |
| `tour_stop/save` | `actions/tour_stop/save.php` | admin | Create or update a tour stop |
| `tour_stop/delete` | `actions/tour_stop/delete.php` | admin | Delete a tour stop |

All actions return `elgg_ok_response()` / `elgg_error_response()`.

## CLI

| Command | Class | Purpose |
|---------|-------|---------|
| `tour:doctor` | `Tour\Cli\DoctorCommand` | Post-migration integrity check — counts `tour_page` / `tour_stop` entities |

Registered via the top-level `'cli_commands'` key in `elgg-plugin.php`.

## Key Views

| View | Purpose |
|------|---------|
| `resources/tour/data` | `/tour/data` AJAX endpoint — returns Joyride HTML or Hopscotch JSON |
| `tour/hopscotch` | Renders Hopscotch JSON from a list of stops |
| `tour/joyride` | Renders Joyride `<ol>` from a list of stops |
| `object/tour_page` | List view for tour pages |
| `object/tour_stop` | List view for tour stops (with drag handle) |
| `forms/tour_page/save` | Tour page editor form |
| `forms/tour_stop/save` | Tour stop editor form |
| `ajax/tour_stop/save` | Inline tour-stop editor opened via colorbox |
| `admin/administer_utilities/tour` | Admin landing — list of tour pages |
| `plugins/tour/settings` | Admin setting: Hopscotch or Joyride |

### View Extensions

Declarative in `elgg-plugin.php` under `view_extensions`. Per the 6.x
"full view name" rule, the entries include the folder prefix (`css/...`):

| Extends | Adds | Purpose |
|---------|------|---------|
| `elgg.css` | `css/tour` | Tour overlay z-index fixes |
| `admin.css` | `css/tour_admin` | Admin list styling for tour_stop |

## JavaScript (ES Modules)

ES module conversion from AMD happened in the 5.x → 6.x step. All three
client-side modules now live under `views/default/elgg/tour/*.mjs` and
are loaded via `elgg_import_esm()`.

| Module file | Used at | Purpose |
|-------------|---------|---------|
| `views/default/elgg/tour/display.mjs` | global init (Bootstrap) | Bind topbar "Help" link, fetch `/tour/data`, hand off to the configured library |
| `views/default/elgg/tour/reorder.mjs` | admin tour view (`admin/administer_utilities/tour/view.php`) | jQuery UI sortable for tour stops (`import 'jquery-ui/widgets/sortable'`) |
| `views/default/elgg/tour/edit.mjs` | (currently inactive) | Click-to-pick UI for selecting a DOM target when editing a stop |

Each ESM uses `import 'jquery'` and `import elgg from 'elgg'`. The 3rd-
party Joyride / Hopscotch scripts are **not** ESMs — they are loaded as
plain external `<script>` tags via `elgg_register_external_file('js', ...)`
+ `elgg_load_external_file('js', ...)` in Bootstrap. The display ESM then
calls into the resulting browser global (`window.hopscotch.startTour(...)`
or jQuery `.joyride(...)`).

## Dependencies

### Required plugins
- None (core only). `bodyology_theme` consumes `Tour\Page` / `Tour\Stop`
  via `class_exists` guards but does not require this plugin.

### Composer
- `php >=8.3`
- `elgg/elgg ~7.0.0`
- `composer/installers ^2.0`
- `ext-intl *` (required by Elgg 7.x)
- `minimum-stability: dev` + `prefer-stable: true` (rule 023-composer-stability)
- `repositories[]: https://asset-packagist.org` (rule 023-composer-stability)

### Vendored libraries (do not modify)
- `vendors/hopscotch/` — Hopscotch tour library
- `vendors/joyride/` — Joyride tour library (ships with its own jquery-1.10.1 + joyride-2.1)

## Seeding

This plugin owns the following entity types and ships a `Seeder` subclass
(`Tour\Database\Seeds\Seeder`):

- `object/tour_page`
- `object/tour_stop`

**Seed dev/QA data:**
```bash
php vendor/bin/elgg-cli database:seed --type=tour --limit=10
php vendor/bin/elgg-cli database:unseed --type=tour
```

Verified end-to-end on Elgg 7.x: both `seed` and `unseed` run cleanly.

## Migration Notes

### 6.x → 7.x on 2026-05-24

**Automated rules applied** (`skills/elgg-migrate/rules/6x-to-7x`):
- `composer-stability-settings-7x`: APPLIED — added
  `minimum-stability: dev`, `prefer-stable: true`, and the asset-packagist
  repository entry to `composer.json` (rule 023).
- `reset-system-cache-7x`: SKIP — no `elgg_reset_system_cache()` calls.
- `add-docblocks`: SKIP — all functions/methods/properties already carry
  docblocks (carried forward from 5.x pass).

**LLM-guided rules surveyed — no matches in this plugin:**
- `001-elggobject-abstract` — plugin already uses `Tour\Page` /
  `Tour\Stop` subclasses, no raw `new ElggObject()`.
- `002-css-crush-removed` — no `$(varname)` CSS Crush syntax in views.
- `003-cache-backends-removed` — no Redis/Memcached config.
- `004-mailer-laminas-to-symfony` — no email send paths.
- `005-font-awesome-v7` — only icon used is `drag-arrow` via
  `elgg_view_icon('drag-arrow')`, which resolves to
  `<span class="elgg-icon elgg-icon-drag-arrow">`. The
  `reorder.mjs` sortable selector targets the same span — works
  regardless of FA glyph mapping.
- `006-notification-handler-renames` — no notification handlers.
- `007-form-action-renames` — uses plugin-owned `tour_page/*` and
  `tour_stop/*` paths; not affected by `blog/save→edit` style renames.
- `008-response-event-changes` — no `ajax_response` / `forward` handlers.
- `009-button-classes-removed` — no `elgg-button-special` or
  `elgg-button-action-done` usage.
- `010-group-route-changes` — no group-collection URL generation.
- `011-action-renames` — no `flush_cache` references.
- `012-members-route-renames` — no `collection:user:user` references.
- `013-messages-parameter-rename` — no messages integration.
- `014-external-pages-rewrite` — no expages integration.
- `015-min-password-length` — no password validation.
- `016-phpunit-12` — no PHPUnit suite present (gap carried forward).
- `017-river-emittable-capability` — entities deliberately exclude
  river (`searchable=false, commentable=false, likable=false`).
- `018-entity-listing-limit-clamped` — admin views call
  `elgg_list_entities` with internal options (no URL-supplied limit).
- `019-ckeditor-v47` — no CKEditor customisation.
- `020-likes-visibility` — likes disabled on both entity types.
- `021-webservices-changes` — no web services exposed.
- `024-grid-css-extension-target` — only extends `elgg.css` and
  `admin.css`, not `elements/grid`.

**LLM-guided rewrites actually performed:**
- `composer.json`: bumped `php` `>=8.2` → `>=8.3`, `elgg/elgg`
  `~6.1.0` → `~7.0.0`. `ext-intl *` already present and remains valid.
- `elgg-plugin.php`: bumped plugin version `6.0.0` → `7.0.0`. No other
  changes — the declarative shape (entities / actions / routes /
  events / view_extensions / cli_commands) is compatible across 6.x
  and 7.x. The `events.register.menu:entity` block already uses the
  7.x-compatible `'FQCN::method' => spec` keyed shape (no legacy
  `[['handler' => ...]]` shape was ever introduced in this plugin).
- `Tour\Bootstrap::init()`: no changes. `elgg_register_external_file()`
  now returns `void` in 7.x, but the plugin never consumed the return
  value; calls remain valid as-is.

**Docker infra**:
- Replaced `docker/` template files with `infra/elgg7/` equivalents
  (Dockerfile, docker-compose.yml, elgg-composer.json,
  elgg-install.sh, .env.example, index.php) per Iron Law 12.

**Data preservation**:
- Subtype string constants `tour_page` and `tour_stop` are unchanged.
- Entity class refs in `elgg-plugin.php` remain string literals (no
  `::class` / `::SUBTYPE`).
- No `Elgg\Upgrade\Batch` script needed (no schema or data shape change).

**Test results** (Elgg 7.x Docker stack via `verify-fleet
--version=elgg7 --only=tour --require-branch=migrate/elgg-7.x`):
- Plugin activation: PASS (first try)
- `elgg-migrate-verify` gates:
  - PHP syntax (excl. vendor/tests): PASS
  - Homepage renders (15360 bytes): PASS
  - Login page renders (15455 bytes): PASS
  - No PHP Fatal/Error in Apache log: PASS
  - PHP_CodeSniffer (Elgg standard): PASS (0 errors)
  - PHPUnit: SKIP (no suite — same documented gap as earlier versions)
- `--verify` (PostMigrationVerifier, target 7.x): PASS (no version
  boundary violations — no 8.x leakage)
- `--security` (SecuritySweep): PASS (0 findings)
- `tour:doctor` CLI: runs cleanly on activated plugin
  (`tour:doctor complete — no issues found`)
- Seeder end-to-end: `database:seed --type=tour --limit=2` and
  `database:unseed --type=tour` both PASS

**Iron Laws status**:
1. Single-step (6.x -> 7.x only): OK
2. Branch `migrate/elgg-7.x`: OK
3. Verified in Docker: OK
4. Pre-migration tests: SKIP (legacy plugin, no PHPUnit baseline; gap
   carried forward from prior versions — `tests/playwright/` and
   `tests/vitest/` directories present but empty)
5. No closures in elgg-plugin.php: OK (Bootstrap class holds runtime regs)
6. Directory name matches composer name: OK (`tour` == `hypejunction/tour`)
7. Only 7.x APIs (no 8.x leakage; `elgg_register_external_file()` void
   return assumed; no `'restorable'` opt-in — entities are admin-only,
   trash UI is not relevant for them): OK
8. Security sweep clean: OK (0 findings)
9. ARCHITECTURE.md updated: OK
10. PHPCS Elgg standard: OK (0 errors)
11. Composer constraints per table: OK (`~7.0.0`, `>=8.3`,
    `ext-intl`, plus 7.x stability settings)
12. Docker infra under `docker/` is elgg7 template: OK
13. Branch based on `migrate/elgg-6.x`: OK

### 5.x → 6.x on 2026-05-24

### 5.x → 6.x on 2026-05-24

**Automated rules applied** (`skills/elgg-migrate/rules/5x-to-6x`):
- `add-docblocks`: skip — all functions/methods/properties already
  carried docblocks after the 5.x pass.

**LLM-guided rewrites**:
- `composer.json`: bumped `elgg/elgg` from `~5.1.0` to `~6.1.0`.
  PHP `>=8.2` and `ext-intl *` were already set in 5.x and remain valid.
- `elgg-plugin.php`: bumped plugin version `5.0.0` -> `6.0.0`. No other
  changes — the declarative shape (entities / actions / routes / events /
  view_extensions / cli_commands) is compatible across 5.x and 6.x.
  View extensions already use full view names (`css/tour`,
  `css/tour_admin`) per the 6.x "full view name" rule.
- `Tour\Bootstrap::init()`:
  - Replaced `elgg_define_js('joyride'\|'hopscotch', [...])` with
    `elgg_register_external_file('js', 'tour.joyride'\|'tour.hopscotch', ...)`
    + `elgg_load_external_file('js', ...)`. The vendored Joyride /
    Hopscotch libraries are NOT ES modules — they expose browser
    globals (`window.hopscotch`, `$.fn.joyride`). Registering them as
    plain external scripts is the correct 6.x equivalent for legacy
    3rd-party JS.
  - Replaced `elgg_require_js('elgg/tour/display')` with
    `elgg_import_esm('elgg/tour/display')`. The module is now a view
    (`views/default/elgg/tour/display.mjs`).
- JavaScript files: rewrote all three AMD modules to ES modules and
  moved them from `views/default/js/elgg/tour/*.js` to
  `views/default/elgg/tour/*.mjs`:
  - `display.mjs` — replaced `define(function(require) { ... })` with
    `import 'jquery'; import elgg from 'elgg';` + top-level code. The
    runtime `require([library], function(lib) { ... })` is gone — we
    rely on the 3rd-party library global (window.hopscotch or
    $.fn.joyride) being present because Bootstrap registers it as a
    site-wide external file.
  - `edit.mjs` — same AMD-to-ESM rewrite. Module remains inactive
    (no view currently imports it).
  - `reorder.mjs` — added `import 'jquery-ui/widgets/sortable'` for
    the side-effect import; preserved sortable() behavior.
- `views/default/admin/administer_utilities/tour/view.php`: replaced
  `elgg_require_js('elgg/tour/reorder')` with
  `elgg_import_esm('elgg/tour/reorder')`.

**Docker infra**:
- Replaced `docker/` template files with `infra/elgg6/` equivalents
  (Dockerfile, docker-compose.yml, elgg-composer.json, elgg-install.sh,
  .env.example, index.php) per Iron Law 12.

**Data preservation**:
- Subtype string constants `tour_page` and `tour_stop` are unchanged.
- Entity class refs in `elgg-plugin.php` remain string literals (no
  `::class` / `::SUBTYPE`).
- No `Elgg\Upgrade\Batch` script needed (no schema or data shape change).

**Test results** (Elgg 6.x Docker stack via `elgg-migrate-run up tour --version=elgg6`):
- Plugin activation: PASS (first try)
- `elgg-migrate-verify` gates:
  - PHP syntax (excl. vendor/tests): PASS
  - Homepage renders (14728 bytes): PASS
  - Login page renders (14823 bytes): PASS
  - No PHP Fatal/Error in Apache log: PASS
  - PHP_CodeSniffer (Elgg standard): PASS (0 errors)
  - PHPUnit: SKIP (no suite — same documented gap as earlier versions)
- Verified in BOTH library modes:
  - Default (hopscotch): all 5 gates PASS, homepage 14728 bytes
  - Joyride: all 5 gates PASS, homepage 14733 bytes
- `--verify` (PostMigrationVerifier, target 6.x): PASS (no version
  boundary violations — no 7.x leakage)
- `--security` (SecuritySweep): PASS (0 findings)
- `tour:doctor` CLI: runs cleanly on activated plugin
  (`tour:doctor complete — no issues found`)
- Seeder end-to-end: `database:seed --type=tour --limit=2` and
  `database:unseed --type=tour` both PASS

**Iron Laws status**:
1. Single-step (5.x -> 6.x only): OK
2. Branch `migrate/elgg-6.x`: OK
3. Verified in Docker: OK
4. Pre-migration tests: SKIP (legacy plugin, no PHPUnit baseline; gap
   carried forward from prior versions — `tests/playwright/` and
   `tests/vitest/` directories present but empty)
5. No closures in elgg-plugin.php: OK (Bootstrap class holds runtime regs)
6. Directory name matches composer name: OK (`tour` == `hypejunction/tour`)
7. Only 6.x APIs (no 7.x leakage — no `'restorable' => true`, no
   `elgg_register_external_file` void-return assumption, no 7.x-only
   capabilities): OK
8. Security sweep clean: OK (0 findings)
9. ARCHITECTURE.md updated: OK
10. PHPCS Elgg standard: OK (0 errors)
11. Composer constraints per table: OK (`~6.1.0`, `>=8.2`, `ext-intl`)
12. Docker infra under `docker/` is elgg6 template: OK
13. Branch based on `migrate/elgg-5.x`: OK

## For Future Migrations

### Next step: JS rework (joyride → shepherd.js) — bead `elgg-migrate-4yai3`
This is the next blocker on epic `83zi2`. The PHP layer is fully on 7.x
and behaves correctly today; the work below replaces the vendored
joyride / hopscotch shims with a modern tour library:
- [ ] Replace `vendors/joyride/` and `vendors/hopscotch/` with
      `shepherd.js` (MIT, ESM-first, closest joyride API mapping).
- [ ] Drop `js_library` plugin setting + the Hopscotch/Joyride branch
      in `Tour\Bootstrap::init()`. Replace the two
      `elgg_register_external_file('js', 'tour.{joyride,hopscotch}', ...)`
      blocks with a single ESM import of shepherd in `display.mjs`.
- [ ] Rewrite `views/default/tour/{hopscotch,joyride}.php` and
      `resources/tour/data.php` to emit the shepherd JSON shape.
- [ ] Retire the vendored jquery-1.10.1 baggage from joyride.

### 7.x → 8.x checklist (placeholder)
- [ ] Confirm `elgg_register_external_file()` signature still void
- [ ] Re-audit `'restorable'` capability (still not relevant for
      admin-only tour_page / tour_stop, but worth re-checking)
- [ ] Re-run `composer-stability-settings` rule (likely still required)

### Known issues / debt
- Plugin has zero automated test coverage. Both PHPUnit fixtures (entity
  CRUD, action contracts) and Playwright end-to-end (admin flow, tour
  overlay rendering) should be added before further work. The
  `4yai3` JS rework is the natural moment to introduce Playwright
  coverage of the actual tour overlay rendering.
- Vendored joyride library bundles its own `jquery-1.10.1.js` with
  deprecated jQuery 3.x APIs. Replacement by shepherd.js (per epic
  `4yai3`) will retire this debt.
- The display.mjs module mutates global jQuery selector state (`$('*').hover`
  in edit.mjs is currently inactive but would be a perf concern if revived).
