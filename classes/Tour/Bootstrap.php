<?php

namespace Tour;

use Elgg\DefaultPluginBootstrap;

/**
 * Plugin bootstrap.
 *
 * Holds runtime registrations that cannot live in the declarative
 * elgg-plugin.php: external CSS/JS file registrations that depend on the
 * admin-selected JS library (Hopscotch vs Joyride) and the topbar menu
 * item that drives the in-page tour.
 */
class Bootstrap extends DefaultPluginBootstrap {

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function init() {
		$js_lib = (string) \elgg_get_plugin_setting('js_library', 'tour');
		$site_url = \elgg_get_site_url();

		if ($js_lib === 'joyride') {
			\elgg_register_external_file('css', 'joyride', $site_url . 'mod/tour/vendors/joyride/joyride-2.1.css');
			\elgg_load_external_file('css', 'joyride');

			// Joyride / Hopscotch are legacy 3rd-party scripts that expose a
			// global (`$.fn.joyride`, `window.hopscotch`). They are not ES
			// modules — register them as plain external <script> files and
			// load them site-wide so the ESM display module can rely on the
			// global being present.
			\elgg_register_external_file('js', 'tour.joyride', $site_url . 'mod/tour/vendors/joyride/jquery.joyride-2.1.js');
			\elgg_load_external_file('js', 'tour.joyride');
		} else {
			\elgg_register_external_file('css', 'hopscotch', $site_url . 'mod/tour/vendors/hopscotch/css/hopscotch.min.css');
			\elgg_load_external_file('css', 'hopscotch');

			\elgg_register_external_file('js', 'tour.hopscotch', $site_url . 'mod/tour/vendors/hopscotch/hopscotch.min.js');
			\elgg_load_external_file('js', 'tour.hopscotch');
		}

		// Our own display module is an ES module view (views/default/elgg/tour/display.mjs)
		\elgg_import_esm('elgg/tour/display');

		\elgg_register_menu_item('topbar', [
			'name' => 'tour',
			'href' => '#',
			'text' => \elgg_echo('tour:start'),
			'id' => 'tour-start',
			'section' => 'alt',
			'link_class' => 'elgg-topbar-dropdown-link',
			'data-library' => $js_lib,
		]);

		// Register seeder for fleet seeding / fixture creation.
		\elgg_register_event_handler('seeds', 'database', [\Tour\Database\Seeds\Seeder::class, 'addSeed']);
	}
}
