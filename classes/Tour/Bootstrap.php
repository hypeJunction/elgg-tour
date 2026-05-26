<?php

namespace Tour;

use Elgg\DefaultPluginBootstrap;

/**
 * Plugin bootstrap.
 *
 * Registers the topbar "Help" link that triggers the in-page tour, loads the
 * Shepherd tour stylesheet, and imports the ESM display module that drives
 * the tour at runtime.
 *
 * 7.1.0: dropped joyride + hopscotch (legacy jQuery / global-window libs)
 * in favour of shepherd.js — a single MIT-licensed ESM library.
 */
class Bootstrap extends DefaultPluginBootstrap {

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function init() {
		$site_url = elgg_get_site_url();

		elgg_register_external_file('css', 'tour.shepherd', $site_url . 'mod/tour/vendors/shepherd/shepherd.css');
		elgg_load_external_file('css', 'tour.shepherd');

		// shepherd.mjs is loaded lazily by views/default/elgg/tour/display.mjs
		// via a static-asset URL import (`mod/tour/vendors/shepherd/shepherd.mjs`),
		// so no <script> tag is registered for it here.
		elgg_import_esm('elgg/tour/display');

		elgg_register_menu_item('topbar', [
			'name' => 'tour',
			'href' => '#',
			'text' => elgg_echo('tour:start'),
			'id' => 'tour-start',
			'section' => 'alt',
			'link_class' => 'elgg-topbar-dropdown-link',
		]);

		elgg_register_event_handler('seeds', 'database', [\Tour\Database\Seeds\Seeder::class, 'addSeed']);
	}
}
