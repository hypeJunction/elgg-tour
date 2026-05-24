<?php
/**
 * Plugin for managing and displaying feature tours.
 *
 * Static routes, entities, actions, and hooks live in elgg-plugin.php.
 * start.php only registers items that depend on plugin settings
 * (the choice between Hopscotch and Joyride is admin-configurable).
 */

return function () {
	elgg_register_event_handler('init', 'system', 'tour_init');
};

/**
 * Register the admin-selectable tour library, theme extensions, and menu items.
 *
 * @return void
 */
function tour_init() {
	$js_lib = elgg_get_plugin_setting('js_library', 'tour');

	if ($js_lib === 'joyride') {
		elgg_register_css('joyride', '/mod/tour/vendors/joyride/joyride-2.1.css');
		elgg_load_css('joyride');

		elgg_define_js('joyride', [
			'src' => '/mod/tour/vendors/joyride/jquery.joyride-2.1.js',
			'exports' => 'joyride',
		]);
	} else {
		elgg_register_css('hopscotch', '/mod/tour/vendors/hopscotch/css/hopscotch.min.css');
		elgg_load_css('hopscotch');

		elgg_define_js('hopscotch', [
			'src' => '/mod/tour/vendors/hopscotch/hopscotch.min.js',
			'exports' => 'hopscotch',
		]);
	}

	elgg_require_js('elgg/tour/display');

	elgg_extend_view('elgg.css', 'css/tour');
	elgg_extend_view('admin.css', 'css/tour_admin');

	elgg_register_admin_menu_item('administer', 'tour', 'administer_utilities');

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
