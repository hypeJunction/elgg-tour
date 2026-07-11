<?php

namespace Tour;

use Elgg\UnitTestCase;
use Tour\Cli\DoctorCommand;
use Tour\Database\Seeds\Seeder;
use Tour\Page\EntityMenu;

/**
 * Regression coverage for the Elgg 7.x migration fixes applied to the `tour` plugin.
 *
 * These assert the FIXED behaviour so a future edit that reintroduces the pre-migration
 * bug fails loudly rather than silently.
 */
class MigrationFixesUnitTest extends UnitTestCase {

	public function up() {}

	public function down() {}

	public function getPluginID(): string {
		return 'tour';
	}

	/**
	 * Regression b1ee7db: DoctorCommand used static $defaultName which symfony/console 7
	 * ignores, leaving every elgg-cli invocation broken on any site with tour active. The
	 * name is now set via setName() in configure(), which Symfony calls from the constructor.
	 */
	public function testDoctorCommandNameIsResolved(): void {
		$command = new DoctorCommand();

		$this->assertSame('tour:doctor', $command->getName());
		$this->assertNotEmpty($command->getDescription());
	}

	/**
	 * Regression b9e557a: reorder.mjs imported the bare specifier
	 * 'jquery-ui/widgets/sortable', absent from the Elgg 7 importmap. It must import the
	 * full 'jquery-ui' bundle so .sortable() resolves at runtime.
	 */
	public function testReorderModuleImportsFullJqueryUiBundle(): void {
		$mjs = file_get_contents(dirname(__DIR__, 4) . '/views/default/elgg/tour/reorder.mjs');

		$this->assertIsString($mjs);
		$this->assertStringContainsString("import 'jquery-ui';", $mjs);
		$this->assertStringNotContainsString('jquery-ui/widgets/sortable', $mjs);
	}

	/**
	 * Regression (Seeder + Bootstrap): addSeed uses the Elgg 7 event API — it reads the
	 * current value array, APPENDS its own class, and returns the array (no ->add()/void).
	 */
	public function testSeederAddSeedAppendsClassToEventValueArray(): void {
		$event = $this->getMockBuilder(\Elgg\Event::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getValue')->willReturn(['Some\\Other\\Seeder']);

		$result = Seeder::addSeed($event);

		$this->assertSame(['Some\\Other\\Seeder', Seeder::class], $result);
	}

	public function testSeederTypeIsTour(): void {
		$this->assertSame('tour', Seeder::getType());
	}

	/**
	 * Regression (EntityMenu setUp): the menu:entity handler must null-guard on the entity
	 * type so it does not mutate menus for foreign entities.
	 */
	public function testPageEntityMenuReturnsNullForNonPageEntity(): void {
		$event = $this->getMockBuilder(\Elgg\Event::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getEntityParam')->willReturn(new Stop());
		$event->method('getValue')->willReturn([
			\ElggMenuItem::factory(['name' => 'edit', 'text' => 'Edit', 'href' => '/x']),
		]);

		$this->assertNull(EntityMenu::setUp($event));
	}

	/**
	 * Regression (EntityMenu setUp): for a Tour\Page the handler keeps only the
	 * access/edit/delete items (Elgg 7 array-return menu registration) and rewrites the
	 * edit href to the admin tour editor.
	 */
	public function testPageEntityMenuKeepsAllowedItemsAndRewritesEditHref(): void {
		$page = new Page();

		$event = $this->getMockBuilder(\Elgg\Event::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getEntityParam')->willReturn($page);
		$event->method('getValue')->willReturn([
			\ElggMenuItem::factory(['name' => 'edit', 'text' => 'Edit', 'href' => '/legacy/edit']),
			\ElggMenuItem::factory(['name' => 'access', 'text' => 'Access', 'href' => '#']),
			\ElggMenuItem::factory(['name' => 'delete', 'text' => 'Delete', 'href' => '/legacy/delete']),
			\ElggMenuItem::factory(['name' => 'view', 'text' => 'View', 'href' => '/legacy/view']),
		]);

		$result = EntityMenu::setUp($event);

		$this->assertIsArray($result);

		$names = array_map(static fn ($item) => $item->getName(), $result);
		$this->assertContains('edit', $names);
		$this->assertContains('access', $names);
		$this->assertContains('delete', $names);
		$this->assertNotContains('view', $names, 'non-whitelisted items must be stripped');

		$edit = null;
		foreach ($result as $item) {
			if ($item->getName() === 'edit') {
				$edit = $item;
			}
		}

		$this->assertNotNull($edit);
		$this->assertStringContainsString('admin/administer_utilities/tour/edit', $edit->getHref());
	}
}
