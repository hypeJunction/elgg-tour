<?php

// Note: do NOT dereference `::SUBTYPE` or `::class` from plugin-owned
// classes here. elgg-plugin.php is loaded during plugin discovery,
// before the plugin's own classes/ autoloader is wired in. Literals only.

return [
	'plugin' => [
		'name' => 'Tour',
		'version' => '3.0.0',
	],
	'entities' => [
		[
			'type' => 'object',
			'subtype' => 'tour_page',
			'class' => 'Tour\\Page',
			'searchable' => false,
		],
		[
			'type' => 'object',
			'subtype' => 'tour_stop',
			'class' => 'Tour\\Stop',
			'searchable' => false,
		],
	],
	'actions' => [
		'tour_page/save' => [
			'access' => 'admin',
		],
		'tour_page/reorder' => [
			'access' => 'admin',
		],
		'tour_page/delete' => [
			'access' => 'admin',
		],
		'tour_stop/save' => [
			'access' => 'admin',
		],
		'tour_stop/delete' => [
			'access' => 'admin',
		],
	],
	'routes' => [
		// AJAX endpoint consumed by elgg/tour/display module to fetch tour
		// data for the current page. Returns Hopscotch JSON or Joyride HTML
		// depending on the admin-configured library.
		'default:view:tour:data' => [
			'path' => '/tour/data',
			'resource' => 'tour/data',
		],
	],
	'hooks' => [
		'register' => [
			'menu:entity' => [
				'Tour\\Page\\EntityMenu::setUp' => [],
				'Tour\\Stop\\EntityMenu::setUp' => [],
			],
		],
	],
];
