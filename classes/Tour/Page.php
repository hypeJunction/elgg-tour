<?php

namespace Tour;

use ElggObject;

/**
 * Tour page entity — groups a collection of tour stops for a specific URL.
 */
class Page extends ElggObject {

	const SUBTYPE = 'tour_page';

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['subtype'] = self::SUBTYPE;
	}

	/**
	 * Get URL of the tour page.
	 *
	 * @return string
	 */
	public function getURL(): string {
		return \elgg_normalize_url("admin/administer_utilities/tour/view?guid={$this->guid}");
	}
}
