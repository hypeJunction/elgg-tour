<?php

namespace Tour\Stop;

use Elgg\Event;

/**
 * Trims and tweaks the entity menu for Tour\Stop objects.
 */
class EntityMenu {

	/**
	 * Set up entity menu for tour_stop objects.
	 *
	 * Keeps only access/edit/delete items and rewrites the edit link to
	 * the admin tour-stop editor.
	 *
	 * @param \Elgg\Event $event 'register' on 'menu:entity'
	 *
	 * @return \Elgg\Menu\MenuItems|array|null
	 */
	public static function setUp(Event $event) {
		$entity = $event->getEntityParam();
		if (!$entity instanceof \Tour\Stop) {
			return null;
		}

		$return = $event->getValue();
		$allowed = ['access', 'edit', 'delete'];

		foreach ($return as $key => $item) {
			if (!in_array($item->getName(), $allowed, true)) {
				if (is_object($return) && method_exists($return, 'remove')) {
					$return->remove($item->getName());
				} else {
					unset($return[$key]);
				}

				continue;
			}

			if ($item->getName() === 'edit') {
				$item->setHref("admin/administer_utilities/tour/stop/edit?guid={$entity->guid}");
			}
		}

		return $return;
	}
}
