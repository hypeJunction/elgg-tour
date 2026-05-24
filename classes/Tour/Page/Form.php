<?php

namespace Tour\Page;

/**
 * Form preparation helper for the tour_page/save form.
 */
class Form {

	/**
	 * Build the form variables for the tour_page editor.
	 *
	 * @param \Tour\Page|null $entity Existing entity being edited, or null for a new page.
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
			'page' => null,
		];

		if ($entity) {
			foreach ($fields as $name => $value) {
				$fields[$name] = $entity->$name;
			}
		}

		return $fields;
	}
}
