<?php

declare(strict_types=1);

namespace Tour\Database\Seeds;

use Elgg\Database\Seeds\Seed;
use Elgg\Event;

/**
 * Seed / unseed tour plugin entities.
 *
 * Owned entity types: 'tour_page', 'tour_stop'
 *
 * Register via:
 *   elgg_register_event_handler('seeds', 'database', [Seeder::class, 'addSeed']);
 *
 * Run with:
 *   php elgg-cli database:seed --type=tour
 *   php elgg-cli database:unseed --type=tour
 */
final class Seeder extends Seed {

	/**
	 * Identifies this seeder to the CLI: --type=tour
	 *
	 * @return string
	 */
	public static function getType(): string {
		return 'tour';
	}

	/**
	 * Options passed to elgg_get_entities() when counting existing seeds.
	 *
	 * @return array<string, mixed>
	 */
	protected function getCountOptions(): array {
		return [
			'type' => 'object',
			'metadata_name_value_pairs' => [
				['name' => '__faker', 'value' => true],
			],
		];
	}

	/**
	 * Create one seeded entity of each owned type.
	 *
	 * @return void
	 */
	public function seed(): void {
		// object/tour_page
		$entity = $this->createObject([
			'subtype' => 'tour_page',
		]);
		$entity->title = $this->faker->sentence(3);
		$entity->description = $this->faker->paragraph();
		$entity->__faker = true;
		$entity->save();

		// object/tour_stop
		$entity = $this->createObject([
			'subtype' => 'tour_stop',
		]);
		$entity->title = $this->faker->sentence(3);
		$entity->description = $this->faker->paragraph();
		$entity->__faker = true;
		$entity->save();
	}

	/**
	 * Delete all entities previously created by this seeder (tagged __faker=true).
	 *
	 * @return void
	 */
	public function unseed(): void {
		// Unseed object/tour_page
		$entities = \elgg_get_entities([
			'type' => 'object',
			'subtype' => 'tour_page',
			'metadata_name_value_pairs' => [['name' => '__faker', 'value' => true]],
			'limit' => false,
		]);
		foreach ($entities as $e) {
			$e->delete();
		}

		// Unseed object/tour_stop
		$entities = \elgg_get_entities([
			'type' => 'object',
			'subtype' => 'tour_stop',
			'metadata_name_value_pairs' => [['name' => '__faker', 'value' => true]],
			'limit' => false,
		]);
		foreach ($entities as $e) {
			$e->delete();
		}
	}

	/**
	 * Event handler for 'seeds', 'database' — appends this class to the seeds list.
	 *
	 * @param \Elgg\Event $event 'seeds' on 'database'
	 *
	 * @return array
	 */
	public static function addSeed(Event $event): array {
		$value = $event->getValue() ?? [];
		$value[] = __CLASS__;
		return $value;
	}
}
