<?php
/**
 * Provide tour data for the client.
 *
 * Returns a shepherd.js step-configuration JSON document built from the
 * Tour\Page entity associated with the requested page handler.
 */

$page = get_input('page');

$pages = elgg_get_entities([
	'type' => 'object',
	'subtype' => \Tour\Page::SUBTYPE,
	'metadata_name_value_pairs' => [
		[
			'name' => 'page',
			'value' => $page,
		],
	],
	'limit' => false,
]);

$stops = [];

if ($pages) {
	$page_guid = $pages[0]->guid;

	$stops = elgg_get_entities([
		'type' => 'object',
		'subtype' => \Tour\Stop::SUBTYPE,
		'container_guid' => $page_guid,
		'order_by_metadata' => [
			'name' => 'order',
			'direction' => 'ASC',
			'as' => 'integer',
		],
		'order_by' => 'e.time_created ASC',
		'limit' => false,
	]);
}

elgg_set_http_header('Content-Type: application/json');
echo elgg_view('tour/shepherd', ['stops' => $stops]);
