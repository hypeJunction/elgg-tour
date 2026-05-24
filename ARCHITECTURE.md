# Tour — Architecture (Elgg 3.x)

## Summary

**Name**: Tour
**Version**: 3.0.0 — migrated to Elgg 3.x on 2026-05-24 (from 2.x)
**Purpose**: Manage and display in-app feature tours for Elgg sites.

A site admin defines per-URL tour pages (`Tour\Page`) and a list of
ordered tour stops (`Tour\Stop`) that target DOM elements on that URL.
At runtime, an end user opens the topbar "Help" link and the
admin-selected client library (Hopscotch or Joyride) draws the tour
overlay using stop metadata fetched from the `/tour/data` AJAX endpoint.

## Directory Structure

```
tour/
├── elgg-plugin.php                   # Declarative entities/actions/routes/hooks
├── start.php                         # Closure-returning bootstrap; dynamic JS lib selection only
├── manifest.xml                      # Still required in 3.x
├── composer.json                     # PSR-4 autoload + elgg/elgg ^3.0
├── classes/
│   └── Tour/
│       ├── Page.php                  # ElggObject subtype 'tour_page'
│       ├── Stop.php                  # ElggObject subtype 'tour_stop'
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
| object | `tour_page` | `Tour\Page` | not searchable, not commentable |
| object | `tour_stop` | `Tour\Stop` | not searchable, not commentable |

Subtype constants live on the entity classes (`Tour\Page::SUBTYPE`,
`Tour\Stop::SUBTYPE`) and were preserved across migration — no DB
migration needed.

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

Both handlers use the 3.x `\Elgg\Hook` single-arg signature and handle
both array and `Elgg\Menu\MenuItems` return shapes.

## Actions

| Action | File | Access | Purpose |
|--------|------|--------|---------|
| `tour_page/save` | `actions/tour_page/save.php` | admin | Create or update a tour page |
| `tour_page/delete` | `actions/tour_page/delete.php` | admin | Delete a tour page |
| `tour_page/reorder` | `actions/tour_page/reorder.php` | admin | Reorder tour stops within a page |
| `tour_stop/save` | `actions/tour_stop/save.php` | admin | Create or update a tour stop |
| `tour_stop/delete` | `actions/tour_stop/delete.php` | admin | Delete a tour stop |

All actions return `elgg_ok_response()` / `elgg_error_response()`
(3.x action contract). No leftover `forward()` calls.

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

| Extends | Adds | Purpose |
|---------|------|---------|
| `elgg.css` | `css/tour` | Tour overlay z-index fixes |
| `admin.css` | `css/tour_admin` | Admin list styling for tour_stop |

## JavaScript (AMD)

| Module | Used at | Purpose |
|--------|---------|---------|
| `elgg/tour/display` | global init | Bind topbar "Help" link, fetch `/tour/data`, hand off to the configured library |
| `elgg/tour/reorder` | admin tour view | jQuery UI sortable for tour stops, posts new order to `tour_page/reorder` |
| `elgg/tour/edit` | (currently inactive) | Click-to-pick UI for selecting a DOM target when editing a stop |

## Dependencies

### Required plugins
- None (core only). `bodyology_theme` consumes `Tour\Page` / `Tour\Stop`
  via `class_exists` guards but does not require this plugin.

### Composer
- `php >=7.2`
- `elgg/elgg ^3.0`
- `composer/installers ~1.0`

### Vendored libraries (do not modify)
- `vendors/hopscotch/` — Hopscotch tour library
- `vendors/joyride/` — Joyride tour library

## Seeding

This plugin does NOT ship a `\Elgg\Database\Seeds\Seed` subclass. Tour
data is content-specific (admins author it once per site URL) and seeding
random pages/stops with no targets in the rendered DOM would produce no
useful dev/QA value. Documented absence per the migration acceptance gate.

## Migration Notes

### 2.x → 3.x on 2026-05-24

**Automated rules applied** (`skills/elgg-migrate/rules/2x-to-3x`):
- `update-manifest-version`: `manifest.xml` elgg_release 1.11.0 → 3.0
- `page-handler-to-route`: `elgg_register_page_handler()` → `elgg_register_route()` (×2)
- `subtype-registration`: `add_subtype/update_subtype` → `elgg_set_entity_class()`
- `deprecated-entity-queries`: `elgg_*_entities_from_metadata()` → unified `elgg_*_entities()` (×3)
- `removed-functions`: drop `elgg_register_viewtype('json')`
- `elgg-register-ajax-view`: drop `elgg_register_ajax_view()` (views are ajax-capable by default)

**LLM-guided rewrites**:
- Added `elgg-plugin.php` with declarative entities/actions/routes/hooks
  (uses string literals because plugin classes/ autoloader is not yet
  wired in at config load time — `\Tour\Page::SUBTYPE` references would
  fatal with "Class not found" during `generateEntities()`)
- Rewrote `start.php` as a closure-returning bootstrap (3.x convention)
- Deleted `activate.php` and `deactivate.php` (entity-class registration
  moves to elgg-plugin.php `entities` array; the old `add_subtype`
  reconciliation is no longer needed)
- Created `composer.json` from `manifest.xml` with PSR-4 autoload
- Moved `views/default/tour/data.php` to `views/default/resources/tour/data.php`
  to back the new `/tour/data` route
- Removed dead `tour_stop` page-handler alias (only `/tour/data` was
  actually consumed by the JS client)
- Rewrote menu hooks (`Tour\Page\EntityMenu`, `Tour\Stop\EntityMenu`)
  to single-arg `\Elgg\Hook` signature, handling both array and
  `Elgg\Menu\MenuItems` return shapes
- Converted all 5 actions to return `elgg_ok_response()` /
  `elgg_error_response()` (3.x action contract)
- Fixed pre-existing bug: `register_error('tour:not_found')` was passing
  a translation key, not the translated string
- Converted heredoc syntax in views to alt PHP/HTML (Elgg sniffs forbid heredoc)
- Added missing language strings: `tour:error:stop_not_found`,
  `tour:error:unauthorized`, `tour:action:save:error`
- Reduced `Tour\Stop\Form::prepare()` page lookup to `ElggBatch`
- Added missing class/return docblocks; phpcbf cleaned mechanical whitespace,
  short-array syntax, brace placement, `@inheritDoc` markers

**Data preservation**:
- Subtype string constants `tour_page` and `tour_stop` are unchanged.
  Existing entities remain discoverable via `elgg_get_entities([
  'type' => 'object', 'subtype' => Tour\Page::SUBTYPE ])`. No
  `Elgg\Upgrade\Batch` script needed.

**Manual fixes required**:
- The post-migration verifier and security sweep both passed with zero
  findings — no manual security fix-up needed.

**Test results**:
- PHPUnit: no suite — pre-migration coverage was absent; documenting
  the gap. Plugin is small (5 actions, 4 helper classes); fleet-wide
  smoke testing covers basic activation behavior. Adding a PHPUnit suite
  is filed as a follow-up (epic-level: `elgg-test-writer` skill).
- Playwright: same — no end-to-end coverage in upstream baseline.
- Docker activation (Elgg 3.3.25): PASS
- Homepage render (with Host header): 200, ~9.3KB
- Login render: 200, ~9.0KB
- `/tour/data?page=/` resource route: 200
- No PHP Fatal/Error in Apache log
- PHP_CodeSniffer (Elgg standard): PASS (0 errors, 0 warnings)
- PostMigrationVerifier (`--verify`): PASS
- SecuritySweep (`--security`): PASS

## For Future Migrations

### 3.x → 4.x checklist
- [ ] Delete `start.php`, `manifest.xml`, `activate.php`, `deactivate.php`
      (none of those remain after this migration — only `start.php` is
      left to delete in the 4.x step)
- [ ] Move `start.php` content into a `Tour\Bootstrap` class
      (init/activate/deactivate methods)
- [ ] Lowercase composer name should already be `hypejunction/tour`
- [ ] Move `actions/` registrations into `elgg-plugin.php` `actions` key
      (already done in this 3.x migration)
- [ ] Move `hooks` registrations to declarative `elgg-plugin.php` `hooks` key
      (already done)
- [ ] Convert menu handlers to `function(\Elgg\Hook $hook): \Elgg\Menu\MenuItems`
      signature (already 3.x form, just tighten return types)
- [ ] Add `capabilities` to entity registration (4.x replaces ad-hoc hook
      registrations for commentable/searchable/likable)

### 4.x → 5.x checklist
- [ ] Rename `'hooks'` key in elgg-plugin.php → `'events'`
- [ ] Replace `\Elgg\Hook` type hint with `\Elgg\Event` in
      `Tour\Page\EntityMenu` and `Tour\Stop\EntityMenu`
- [ ] Rename `Tour\*\Hooks\` directories → `Tour\*\Events\` if extracted later

### 5.x → 6.x checklist
- [ ] Convert `views/default/js/elgg/tour/{display,edit,reorder}.js` from
      AMD `define()` to ES module `import/export`
- [ ] Replace `elgg_define_js()` (in start.php) with `elgg_register_esm()`
- [ ] Replace `elgg_require_js()` with `elgg_import_esm()`
- [ ] Re-evaluate whether to drop the vendored Hopscotch/Joyride libraries
      in favor of a modern lightweight alternative (e.g. driver.js, shepherd.js)

### Known issues / debt
- `views/default/admin/administer_utilities/tour/edit.php` still uses
  `forward(REFERER)` rather than returning a response. 3.x permits this
  but 4.x will require migration to a resource view or middleware.
- Plugin has zero automated test coverage. Both PHPUnit fixtures
  (entity CRUD, action contracts) and Playwright end-to-end (admin
  flow, tour overlay rendering) should be added before the 4.x step.
