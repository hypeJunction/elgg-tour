<?php

$guid = (int) get_input('guid');
$container_guid = (int) get_input('container_guid');

$container = get_entity($container_guid);

if (!$container instanceof \Tour\Page) {
	return elgg_error_response(elgg_echo('tour:error:page_not_found'));
}

if (!$container->canEdit()) {
	return elgg_error_response(elgg_echo('tour:error:unauthorized'));
}

if ($guid) {
	$entity = get_entity($guid);

	if (!$entity instanceof \Tour\Stop) {
		return elgg_error_response(elgg_echo('tour:error:stop_not_found'));
	}

	if (!$entity->canEdit()) {
		return elgg_error_response(elgg_echo('tour:error:unauthorized'));
	}
} else {
	$site = elgg_get_site_entity();

	$entity = new \Tour\Stop();
	$entity->owner_guid = $site->guid;
	$entity->order = 999;
}

$entity->container_guid = $container->guid;
$entity->title = get_input('title');
$entity->description = get_input('description');
$entity->target = get_input('target');
$entity->placement = get_input('placement');
$entity->access_id = $container->access_id;

if (!$entity->save()) {
	return elgg_error_response(elgg_echo('tour:action:save:error'));
}

$forward = elgg_normalize_url("admin/administer_utilities/tour/view?guid={$container->guid}");

return elgg_ok_response('', elgg_echo('tour:action:save:success'), $forward);
