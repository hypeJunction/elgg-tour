# Tour — Architecture (Elgg 5.x)

## Summary

**Name**: Tour
**Version**: 5.0.0 — migrated to Elgg 5.x on 2026-05-24 (from 4.x)
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
├── docker/                           # elgg5 Docker infra (per Iron Law 12)
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
│       ├── js/elgg/tour/{display,edit,reorder}.js # AMD modules
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
| `elgg_define_js('joyride'\|'hopscotch', ...)` | Same — admin-selectable JS library |
| `elgg_require_js('elgg/tour/display')` | Loaded site-wide so the topbar link works on every page |
| `elgg_register_menu_item('topbar', ['name' => 'tour', ..., 'data-library' => $js_lib])` | Menu item carries the currently-selected library as a data attribute consumed by the JS module |
| `elgg_register_event_handler('seeds', 'database', [Seeder::class, 'addSeed'])` | Required: Seed subclasses are exposed via the `seeds` event; the closure-free `'events'` key cannot hold static method refs that reference plugin classes from `elgg-plugin.php` |

## Routes

| Name | Path | Resource view |
|------|------|---------------|
| `default:view:tour:data` | `/tour/data` | `tour/data` |

The admin UI (`admin/administer_utilities/tour/...`) is provided by
core's admin context handler — not via plugin-owned routes.

## Events

In Elgg 5.x, hooks and events merged into a single `'events'` key with
`\Elgg\Event` callbacks. Declarative event handlers:

| Event | Type | Handler | Purpose |
|-------|------|---------|---------|
| `register` | `menu:entity` | `Tour\Page\EntityMenu::setUp` | Keep only access/edit/delete on `Tour\Page`; rewrite edit href |
| `register` | `menu:entity` | `Tour\Stop\EntityMenu::setUp` | Keep only access/edit/delete on `Tour\Stop`; rewrite edit href |

Both handlers use the 5.x `\Elgg\Event` single-arg signature and handle
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

All actions return `elgg_ok_response()` / `elgg_error_response()`. No
leftover `forward()` calls — admin edit views that previously used
`forward(REFERER)` throw `\Elgg\Exceptions\Http\EntityNotFoundException`.

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

Declarative in `elgg-plugin.php` under `view_extensions`:

| Extends | Adds | Purpose |
|---------|------|---------|
| `elgg.css` | `css/tour` | Tour overlay z-index fixes |
| `admin.css` | `css/tour_admin` | Admin list styling for tour_stop |

## JavaScript (AMD)

| Module | Used at | Purpose |
|--------|---------|---------|
| `elgg/tour/display` | global init | Bind topbar "Help" link, fetch `/tour/data`, hand off to the configured library |
| `elgg/tour/reorder` | admin tour view | jQuery UI sortable for tour stops (explicit `require('jquery-ui/widgets/sortable')` — still valid in 5.x; bundled jquery-ui split into submodules) |
| `elgg/tour/edit` | (currently inactive) | Click-to-pick UI for selecting a DOM target when editing a stop |

ES module conversion happens in the 5.x → 6.x step.

## Dependencies

### Required plugins
- None (core only). `bodyology_theme` consumes `Tour\Page` / `Tour\Stop`
  via `class_exists` guards but does not require this plugin.

### Composer
- `php >=8.2`
- `elgg/elgg ~5.1.0`
- `composer/installers ^2.0`
- `ext-intl *` (required by Elgg 5.x)

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
php elgg-cli database:seed --type=tour --limit=10
php elgg-cli database:unseed --type=tour
```

Verified end-to-end on Elgg 5.x: both `seed` and `unseed` run cleanly.

## Migration Notes

### 4.x → 5.x on 2026-05-24

**Automated rules applied** (`skills/elgg-migrate/rules/4x-to-5x`):
- `update-manifest-version-5x`: composer `elgg/elgg ^4.3 -> ~5.0.0`,
  `php >=7.4 -> >=8.1`, added `ext-intl *` requirement. Constraints
  later tightened by hand to the documented table values: `~5.1.0`
  for `elgg/elgg` and `>=8.2` for PHP per SKILL.md's Composer
  Requirements table.
- `scaffold-seeder`: generated `classes/Tour/Database/Seeds/Seeder.php`
  for `tour_page` and `tour_stop` subtypes, appended a `Seeding`
  section to ARCHITECTURE.md.

**LLM-guided rewrites**:
- `elgg-plugin.php`: renamed `'hooks'` key -> `'events'` (entries
  unchanged); bumped plugin version `4.0.0` -> `5.0.0`.
- `Tour\Page\EntityMenu::setUp` and `Tour\Stop\EntityMenu::setUp`:
  swapped `\Elgg\Hook` -> `\Elgg\Event` (type hint + param name +
  docblock). Method bodies unchanged — `getEntityParam()`,
  `getValue()`, return-shape handling are identical between the
  two classes.
- `Tour\Bootstrap::init()`: appended runtime registration of the
  seeder event handler `elgg_register_event_handler('seeds',
  'database', [\Tour\Database\Seeds\Seeder::class, 'addSeed'])`.
  The seeder cannot be wired via the declarative `'events'` key in
  `elgg-plugin.php` cleanly (FQCN reference would require autoload
  side-effects at config load), so Bootstrap is the correct home.
- `Tour\Page::getURL()`: added strict return type `: string` to
  match the new 5.x `ElggEntity::getURL(): string` signature
  (incompatible-signature fatal during seeder run otherwise).
  Wrapped the relative path in `elgg_normalize_url()` so the contract
  ("returns absolute URL") is honored.
- PHPCS auto-fixes on the scaffolded Seeder (`phpcbf --standard=Elgg`):
  86 errors fixed (tabs, brace placement, blank lines); 5 remaining
  missing `@return` / `@param` docblocks added by hand.

**Docker infra**:
- Replaced `docker/` template files with `infra/elgg5/` equivalents
  (Dockerfile, docker-compose.yml, elgg-composer.json, elgg-install.sh,
  .env.example) per Iron Law 12.

**Data preservation**:
- Subtype string constants `tour_page` and `tour_stop` are unchanged.
- Entity class refs in `elgg-plugin.php` remain string literals (no
  `::class` / `::SUBTYPE`) — autoloader is not wired in at config load.
- No `Elgg\Upgrade\Batch` script needed (no schema or data shape change).

**Test results** (Elgg 5.x Docker stack via `elgg-migrate-run up tour --version=elgg5`):
- Plugin activation: PASS (first try after Page::getURL signature fix)
- `elgg-migrate-verify` gates:
  - PHP syntax (excl. vendor/tests): PASS
  - Homepage renders (9428 bytes): PASS
  - Login page renders (9515 bytes): PASS
  - No PHP Fatal/Error in Apache log: PASS
  - PHP_CodeSniffer (Elgg standard): PASS (0 errors)
  - PHPUnit: SKIP (no suite — same documented gap as earlier versions)
- `--verify` (PostMigrationVerifier): PASS (no version-boundary violations)
- `--security` (SecuritySweep): PASS (1 informational warning — plaintext
  HTTP link in `vendors/joyride/demo/demo.html`, vendored 3rd-party file)
- `tour:doctor` CLI: runs cleanly on activated plugin (`tour:doctor complete — no issues found`)
- Seeder end-to-end: `database:seed --type=tour --limit=2` and
  `database:unseed --type=tour` both PASS

**Iron Laws status**:
1. Single-step (4.x -> 5.x only): OK
2. Branch `migrate/elgg-5.x`: OK
3. Verified in Docker: OK
4. Pre-migration tests: SKIP (legacy plugin, no PHPUnit baseline; gap
   documented in earlier ARCHITECTURE notes — Playwright e2e + Vitest
   directories present but empty)
5. No closures in elgg-plugin.php: OK (Bootstrap class holds runtime regs)
6. Directory name matches composer name: OK (`tour` == `hypejunction/tour`)
7. Only 5.x APIs (no `\Elgg\Event` leakage into earlier branches; no
   ES module / `'restorable'` capability / `elgg_register_esm` leakage
   from 6.x+): OK
8. Security sweep clean: OK (only vendored-file warning)
9. ARCHITECTURE.md updated: OK
10. PHPCS Elgg standard: OK (0 errors after phpcbf + manual docblocks)
11. Composer constraints per table: OK (`~5.1.0`, `>=8.2`, `ext-intl`)
12. Docker infra under `docker/` is elgg5 template: OK
13. Branch based on `migrate/elgg-4.x`: OK

## For Future Migrations

### 5.x → 6.x checklist
- [ ] Convert `views/default/js/elgg/tour/{display,edit,reorder}.js` from
      AMD `define()` to ES module `import/export`
- [ ] Replace `elgg_define_js()` (in Bootstrap) with `elgg_register_esm()`
- [ ] Replace `elgg_require_js()` with `elgg_import_esm()`
- [ ] Re-evaluate whether to drop the vendored Hopscotch/Joyride libraries
      in favor of a modern lightweight alternative (e.g. driver.js,
      shepherd.js) — tracked under epic `elgg-migrate-4yai3` (shepherd.js chosen)
- [ ] Bump composer `php` to `>=8.2`, `elgg/elgg` to `~6.1.0`

### 6.x → 7.x checklist
- [ ] Add `'restorable' => true` to entity capabilities for trash/soft-delete
- [ ] Audit any `elgg_register_external_file` usage — signature now returns void in 7.x
- [ ] Bump composer `php` to `>=8.3`, `elgg/elgg` to `~7.0.0`

### Known issues / debt
- Plugin has zero automated test coverage. Both PHPUnit fixtures (entity
  CRUD, action contracts) and Playwright end-to-end (admin flow, tour
  overlay rendering) should be added before the next major step.
- Vendored joyride library bundles its own `jquery-1.10.1.js` with
  deprecated jQuery 3.x APIs. Replacement by shepherd.js (per epic
  `4yai3`) will retire this debt.
