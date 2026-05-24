<?php

// Note: do NOT dereference `::SUBTYPE` or `::class` from plugin-owned
// classes here. elgg-plugin.php is loaded during plugin discovery,
// before the plugin's own classes/ autoloader is wired in. Literals only.

return [
	'plugin' => [
		'name' => 'Tour',
		'version' => '6.0.0',
	],
	'bootstrap' => 'Tour\\Bootstrap',
	'entities' => [
		[
			'type' => 'object',
			'subtype' => 'tour_page',
			'class' => 'Tour\\Page',
			'capabilities' => [
				'searchable' => false,
				'commentable' => false,
				'likable' => false,
			],
		],
		[
			'type' => 'object',
			'subtype' => 'tour_stop',
			'class' => 'Tour\\Stop',
			'capabilities' => [
				'searchable' => false,
				'commentable' => false,
				'likable' => false,
			],
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
	'events' => [
		'register' => [
			'menu:entity' => [
				'Tour\\Page\\EntityMenu::setUp' => [],
				'Tour\\Stop\\EntityMenu::setUp' => [],
			],
		],
	],
	'view_extensions' => [
		'elgg.css' => [
			'css/tour' => [],
		],
		'admin.css' => [
			'css/tour_admin' => [],
		],
	],
	'cli_commands' => [
		'Tour\\Cli\\DoctorCommand',
	],
];
