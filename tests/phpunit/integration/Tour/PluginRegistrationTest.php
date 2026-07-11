<?php

namespace Tour;

use Elgg\IntegrationTestCase;

/**
 * Asserts the elgg-plugin.php manifest actually registers the plugin's entities, actions,
 * routes and menu:entity event handlers on a booted Elgg 7.x.
 */
class PluginRegistrationTest extends IntegrationTestCase {

	public function up() {}

	public function down() {}

	public function getPluginID(): string {
		return 'tour';
	}

	public function testPluginActiveAndEntityClassesRegistered(): void {
		$plugin = elgg_get_plugin_from_id('tour');
		$this->assertNotNull($plugin);
		$this->assertTrue($plugin->isActive());

		// The `entities` block binds the subtypes to the concrete classes. Creating a
		// tour_page/tour_stop must hydrate as Tour\Page / Tour\Stop (also proves the 7.x
		// abstract-ElggObject fix: the concrete subclass is instantiable).
		$page = $this->createObject(['subtype' => 'tour_page', 'access_id' => ACCESS_PUBLIC]);
		$this->assertInstanceOf(Page::class, $page);

		$stop = $this->createObject(['subtype' => 'tour_stop', 'access_id' => ACCESS_PUBLIC]);
		$this->assertInstanceOf(Stop::class, $stop);
	}

	public function testActionsAndRouteRegistered(): void {
		$actions = _elgg_services()->actions;

		foreach (['tour_page/save', 'tour_page/reorder', 'tour_page/delete', 'tour_stop/save', 'tour_stop/delete'] as $action) {
			$this->assertTrue($actions->exists($action), "action {$action} should be registered");
		}

		$this->assertNotNull(
			_elgg_services()->routes->get('default:view:tour:data'),
			'default:view:tour:data route should be registered'
		);
	}

	public function testMenuEntityEventHandlersRegistered(): void {
		$handlers = _elgg_services()->events->getAllHandlers();
		$menu_entity = $handlers['register']['menu:entity'] ?? [];

		$callbacks = [];
		foreach ($menu_entity as $entry) {
			$cb = $entry['callback'] ?? $entry;
			$callbacks[] = is_array($cb) ? implode('::', $cb) : (string) $cb;
		}

		$joined = implode('|', $callbacks);
		$this->assertStringContainsString('Tour\\Page\\EntityMenu', $joined);
		$this->assertStringContainsString('Tour\\Stop\\EntityMenu', $joined);
	}
}
