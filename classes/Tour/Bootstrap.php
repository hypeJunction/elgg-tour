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
		$js_lib = (string) elgg_get_plugin_setting('js_library', 'tour');

		if ($js_lib === 'joyride') {
			elgg_register_external_file('css', 'joyride', elgg_get_site_url() . 'mod/tour/vendors/joyride/joyride-2.1.css');
			elgg_load_external_file('css', 'joyride');

			elgg_define_js('joyride', [
				'src' => '/mod/tour/vendors/joyride/jquery.joyride-2.1.js',
				'exports' => 'joyride',
			]);
		} else {
			elgg_register_external_file('css', 'hopscotch', elgg_get_site_url() . 'mod/tour/vendors/hopscotch/css/hopscotch.min.css');
			elgg_load_external_file('css', 'hopscotch');

			elgg_define_js('hopscotch', [
				'src' => '/mod/tour/vendors/hopscotch/hopscotch.min.js',
				'exports' => 'hopscotch',
			]);
		}

		elgg_require_js('elgg/tour/display');

		elgg_register_menu_item('topbar', [
			'name' => 'tour',
			'href' => '#',
			'text' => elgg_echo('tour:start'),
			'id' => 'tour-start',
			'section' => 'alt',
			'link_class' => 'elgg-topbar-dropdown-link',
			'data-library' => $js_lib,
		]);
	}
}
