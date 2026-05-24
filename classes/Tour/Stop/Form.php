<?php

namespace Tour\Stop;

/**
 * Form preparation helper for the tour_stop/save form.
 */
class Form {

	/**
	 * Build the form variables for the tour_stop editor.
	 *
	 * @param \Tour\Stop|null $entity Existing entity being edited, or null for a new stop.
	 *
	 * @return array
	 */
	public function prepare($entity = null) {
		// name => value
		$fields = [
			'guid' => null,
			'title' => null,
			'description' => null,
			'owner_guid' => null,
			'container_guid' => null,
			'access_id' => ACCESS_PUBLIC,
			'target' => null,
			'placement' => null,
			'page_options' => [],
		];

		if ($entity) {
			foreach ($fields as $name => $value) {
				if ($name === 'page_options') {
					continue;
				}

				$fields[$name] = $entity->$name;
			}
		}

		$pages = elgg_get_entities([
			'type' => 'object',
			'subtype' => \Tour\Page::SUBTYPE,
			'limit' => 0,
			'batch' => true,
		]);

		foreach ($pages as $page) {
			$fields['page_options'][$page->guid] = $page->title;
		}

		return $fields;
	}
}
