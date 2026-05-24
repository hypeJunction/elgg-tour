<?php

$guid = (int) get_input('guid');
$stop_guids = (array) get_input('guids', []);

/* @var $entities \ElggBatch */
$entities = elgg_get_entities([
	'type' => 'object',
	'subtype' => \Tour\Stop::SUBTYPE,
	'container_guid' => $guid,
	'limit' => false,
	'batch' => true,
]);

$count = 0;
foreach ($entities as $entity) {
	$position = array_search($entity->guid, $stop_guids);

	if ($position !== false) {
		$entity->order = $position;
		$count++;
	}
}

return elgg_ok_response('', elgg_echo('tour:action:reorder:success', [$count]));
