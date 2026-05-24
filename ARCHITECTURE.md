# Tour — Architecture (Elgg 4.x)

## Summary

**Name**: Tour
**Version**: 4.0.0 — migrated to Elgg 4.x on 2026-05-24 (from 3.x)
**Purpose**: Manage and display in-app feature tours for Elgg sites.

A site admin defines per-URL tour pages (`Tour\Page`) and a list of
ordered tour stops (`Tour\Stop`) that target DOM elements on that URL.
At runtime, an end user opens the topbar "Help" link and the
admin-selected client library (Hopscotch or Joyride) draws the tour
overlay using stop metadata fetched from the `/tour/data` AJAX endpoint.

## Directory Structure

```
tour/
├── elgg-plugin.php                   # Declarative entities/actions/routes/hooks/cli_commands
├── composer.json                     # Sole plugin metadata in 4.x (manifest.xml deleted)
├── docker/                           # elgg4 Docker infra (per Iron Law 12)
├── classes/
│   └── Tour/
│       ├── Bootstrap.php             # extends DefaultPluginBootstrap; runtime settings-dependent regs
│       ├── Page.php                  # ElggObject subtype 'tour_page'
│       ├── Stop.php                  # ElggObject subtype 'tour_stop'
│       ├── Cli/
│       │   └── DoctorCommand.php     # `tour:doctor` post-migration integrity check
│       ├── Page/
│       │   ├── EntityMenu.php        # menu:entity register hook
│       │   └── Form.php              # tour_page/save form vars
│       └── Stop/
│           ├── EntityMenu.php        # menu:entity register hook
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

Per Iron Law / cross-plugin learning: the `class` entry in elgg-plugin.php
remains a **string literal** (`'Tour\\Page'`), not `\Tour\Page::class` or
`\Tour\Page::SUBTYPE`. elgg-plugin.php is parsed during plugin discovery,
before the plugin's `classes/` PSR-4 autoloader is wired in.

## Bootstrap

`Tour\Bootstrap` extends `\Elgg\DefaultPluginBootstrap` and implements
`init()` to register the runtime, settings-dependent items that cannot
live in the declarative `elgg-plugin.php` config:

| Registration | Why it cannot be declarative |
|--------------|------------------------------|
| `elgg_register_external_file('css', ...)` for joyride **or** hopscotch CSS | Choice depends on `js_library` plugin setting at request time |
| `elgg_define_js('joyride'|'hopscotch', ...)` | Same — admin-selectable JS library |
| `elgg_require_js('elgg/tour/display')` | Loaded site-wide so the topbar link works on every page |
| `elgg_register_menu_item('topbar', ['name' => 'tour', ..., 'data-library' => $js_lib])` | Menu item carries the currently-selected library as a data attribute consumed by the JS module |

## Routes

| Name | Path | Resource view |
|------|------|---------------|
| `default:view:tour:data` | `/tour/data` | `tour/data` |

The admin UI (`admin/administer_utilities/tour/...`) is provided by
core's admin context handler — not via plugin-owned routes.

## Hooks

| Event | Type | Handler | Purpose |
|-------|------|---------|---------|
| `register` | `menu:entity` | `Tour\Page\EntityMenu::setUp` | Keep only access/edit/delete on `Tour\Page`; rewrite edit href |
| `register` | `menu:entity` | `Tour\Stop\EntityMenu::setUp` | Keep only access/edit/delete on `Tour\Stop`; rewrite edit href |

Both handlers use the 4.x `\Elgg\Hook` single-arg signature and handle
both array and `Elgg\Menu\MenuItems` return shapes.

Note (Iron Law 7): in 4.x this stays as `'hooks'` and `\Elgg\Hook`. The
unification under `'events'` / `\Elgg\Event` happens in the 4.x → 5.x step.

## Actions

| Action | File | Access | Purpose |
|--------|------|--------|---------|
| `tour_page/save` | `actions/tour_page/save.php` | admin | Create or update a tour page |
| `tour_page/delete` | `actions/tour_page/delete.php` | admin | Delete a tour page |
| `tour_page/reorder` | `actions/tour_page/reorder.php` | admin | Reorder tour stops within a page |
| `tour_stop/save` | `actions/tour_stop/save.php` | admin | Create or update a tour stop |
| `tour_stop/delete` | `actions/tour_stop/delete.php` | admin | Delete a tour stop |

All actions return `elgg_ok_response()` / `elgg_error_response()`
(4.x action contract). No leftover `forward()` calls — admin edit views
that previously used `forward(REFERER)` now `throw \Elgg\Exceptions\Http\EntityNotFoundException`.

## CLI

| Command | Class | Purpose |
|---------|-------|---------|
| `tour:doctor` | `Tour\Cli\DoctorCommand` | Post-migration integrity check — counts `tour_page` / `tour_stop` entities |

Registered via the top-level `'cli_commands'` key in `elgg-plugin.php`
(NOT `'cli' => ['commands' => ...]` — that key shape does not exist in 4.x).

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
| `elgg/tour/reorder` | admin tour view | jQuery UI sortable for tour stops (explicit `require('jquery-ui/widgets/sortable')` for 4.x granular jQuery UI) |
| `elgg/tour/edit` | (currently inactive) | Click-to-pick UI for selecting a DOM target when editing a stop |

## Dependencies

### Required plugins
- None (core only). `bodyology_theme` consumes `Tour\Page` / `Tour\Stop`
  via `class_exists` guards but does not require this plugin.

### Composer
- `php >=7.4`
- `elgg/elgg ^4.3`
- `composer/installers ^2.0`

### Vendored libraries (do not modify)
- `vendors/hopscotch/` — Hopscotch tour library
- `vendors/joyride/` — Joyride tour library (ships with its own jquery-1.10.1 + joyride-2.1)

## Seeding

This plugin does NOT ship a `\Elgg\Database\Seeds\Seed` subclass. Tour
data is content-specific (admins author it once per site URL) and seeding
random pages/stops with no targets in the rendered DOM would produce no
useful dev/QA value. Documented absence per the migration acceptance gate.

## Migration Notes

### 3.x → 4.x on 2026-05-24

**Automated rules applied** (`skills/elgg-migrate/rules/3x-to-4x`):
- `update-manifest-version-4x`: composer `elgg/elgg ^3.0 -> ^4.3`; `manifest.xml` 3.0 -> 4.0 (file later deleted)
- `jquery-deprecated-apis-4x`: `$.parseJSON()` -> `JSON.parse()` in `views/default/js/elgg/tour/display.js`
- `scaffold-doctor-command`: scaffolded `Tour\Cli\DoctorCommand` (`tour:doctor`)
- `jquery-ui-requires-4x` (manual application): added `require('jquery-ui/widgets/sortable')` to `reorder.js`

**LLM-guided rewrites**:
- Deleted `start.php` (4.x rejects any start.php — even empty)
- Deleted `manifest.xml` (4.x: composer.json is sole metadata source)
- Added `Tour\Bootstrap extends \Elgg\DefaultPluginBootstrap` for runtime
  registrations that depend on plugin settings (CSS/JS library selection,
  topbar menu item)
- Updated `elgg-plugin.php`:
  - bumped plugin version 3.0.0 -> 4.0.0
  - added `'bootstrap' => 'Tour\\Bootstrap'`
  - migrated entity flags (`searchable=false`) into the `'capabilities'` array
  - added `'view_extensions'` declarative section for `elgg.css` and `admin.css`
  - registered CLI command under correct 4.x key `'cli_commands' => [...]`
    (skill's `scaffold-doctor-command` rule wrote `'cli' => ['commands' => ...]`
    which is NOT a valid 4.x key — runtime registration silently fails)
- `Tour\Cli\DoctorCommand`:
  - rewrote `command()` to 4.x `Elgg\Cli\Command` signature (no args, uses
    `$this->output`)
  - renamed internal helper `write()` -> `emit()` (`Elgg\Cli\BaseCommand::write`
    is `final`)
- Replaced `forward(REFERER)` in admin edit views with
  `throw new \Elgg\Exceptions\Http\EntityNotFoundException(...)` (`forward()`
  removed in 4.x)
- Replaced `elgg_register_css()` / `elgg_load_css()` (removed in 4.x) with
  `elgg_register_external_file('css', ...)` / `elgg_load_external_file('css', ...)`
- composer.json: bumped `php` to `>=7.4`, `composer/installers` to `^2.0`
  per the skill's Composer Requirements Per Migration Branch table
- Auto-fixed coding style with `phpcbf --standard=Elgg` after scaffold

**Data preservation**:
- Subtype string constants `tour_page` and `tour_stop` are unchanged
- Entity class refs in `elgg-plugin.php` remain string literals (no
  `::class` / `::SUBTYPE`) — autoloader is not wired in at config load
- No `Elgg\Upgrade\Batch` script needed (no schema or data shape change)

**Test results** (Elgg 4.x Docker stack via `elgg-migrate-run up tour --version=elgg4`):
- Plugin activation: PASS (first try)
- `elgg-migrate-verify` gates:
  - PHP syntax (excl. vendor/tests): PASS
  - Homepage renders (7829 bytes): PASS
  - Login page renders (7829 bytes): PASS
  - No PHP Fatal/Error in Apache log: PASS
  - PHP_CodeSniffer (Elgg standard): PASS (0 errors)
  - PHPUnit: SKIP (no suite — same documented gap as 3.x baseline)
- `--verify` (PostMigrationVerifier): PASS (no version-boundary violations)
- `--security` (SecuritySweep): PASS
- `--audit` (DependencyAudit): SKIP (no composer.lock)
- `tour:doctor` CLI: runs cleanly on activated plugin

**Iron Laws status**:
1. Single-step (3.x -> 4.x only): OK
2. Branch `migrate/elgg-4.x`: OK
3. Verified in Docker: OK
4. Pre-migration tests: SKIP (legacy plugin, no PHPUnit baseline; gap documented in 3.x ARCHITECTURE)
5. No closures in elgg-plugin.php: OK (Bootstrap class holds runtime regs)
6. Directory name matches composer name: OK (`tour` == `hypejunction/tour`)
7. Only 4.x APIs (no `\Elgg\Event` leakage): OK
8. Security sweep clean: OK
9. ARCHITECTURE.md updated: OK
10. PHPCS Elgg standard: OK
11. Composer constraints per table: OK
12. Docker infra under `docker/`: OK
13. Branch based on `migrate/elgg-3.x`: OK

## For Future Migrations

### 4.x → 5.x checklist
- [ ] Rename `'hooks'` key in `elgg-plugin.php` -> `'events'`
- [ ] Replace `\Elgg\Hook` type hint with `\Elgg\Event` in
      `Tour\Page\EntityMenu::setUp` and `Tour\Stop\EntityMenu::setUp`
- [ ] Consider renaming `Tour\Page\EntityMenu` / `Tour\Stop\EntityMenu`
      to `Tour\Page\Events\EntityMenu` etc., consistent with 5.x conventions
- [ ] Form preparation classes: `Tour\Page\Form` / `Tour\Stop\Form` could
      become `Tour\Forms\PrepareTourPageFields` etc. (optional refactor)
- [ ] Bootstrap callback signature change: in 5.x, hooks/events take a single
      `Elgg\Event` arg with `->getValue() / ->getParam() / ->setValue()`
- [ ] Bump composer `php` to `>=8.2` (Elgg 5.x floor) and `elgg/elgg` to `^5.0`
- [ ] Update `DoctorCommand` if `Elgg\Cli\Command` contract drifts

### 5.x → 6.x checklist
- [ ] Convert `views/default/js/elgg/tour/{display,edit,reorder}.js` from
      AMD `define()` to ES module `import/export`
- [ ] Replace `elgg_define_js()` (in Bootstrap) with `elgg_register_esm()`
- [ ] Replace `elgg_require_js()` with `elgg_import_esm()`
- [ ] Re-evaluate whether to drop the vendored Hopscotch/Joyride libraries
      in favor of a modern lightweight alternative (e.g. driver.js, shepherd.js)
      — tracked under epic `elgg-migrate-4yai3` (shepherd.js chosen)

### 6.x → 7.x checklist
- [ ] Add `'restorable' => true` to entity capabilities for trash/soft-delete
- [ ] Audit any `elgg_register_external_file` usage — signature now returns void in 7.x

### Known issues / debt
- Plugin has zero automated test coverage. Both PHPUnit fixtures (entity
  CRUD, action contracts) and Playwright end-to-end (admin flow, tour
  overlay rendering) should be added before the next major step.
- Vendored joyride library bundles its own `jquery-1.10.1.js` with
  deprecated jQuery 3.x APIs (`$.parseJSON`, `.bind`, `.delegate`). The
  `jquery-deprecated-apis-4x` AST rule wanted to rewrite these but the
  changes were reverted — vendored 3rd-party libs should not be modified.
  Replacement by shepherd.js (per epic `4yai3`) will retire this debt.
