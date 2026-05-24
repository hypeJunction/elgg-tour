<?php

namespace Tour;

use ElggObject;

/**
 * Tour stop entity — a single highlighted step inside a tour page.
 */
class Stop extends ElggObject {

	const SUBTYPE = 'tour_stop';

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['subtype'] = self::SUBTYPE;
	}
}
