<?php

$guid = (int) get_input('guid');

if ($guid) {
	$entity = get_entity($guid);

	if (!$entity instanceof \Tour\Page) {
		return elgg_error_response(elgg_echo('tour:error:page_not_found'));
	}

	if (!$entity->canEdit()) {
		return elgg_error_response(elgg_echo('tour:error:unauthorized'));
	}
} else {
	$site = elgg_get_site_entity();

	$entity = new \Tour\Page();
	$entity->owner_guid = $site->guid;
	$entity->container_guid = $site->guid;
}

$page = (string) get_input('page');

// Make sure we have the request URI instead of full URL.
$page = str_replace(elgg_get_site_url(), '', $page);

$entity->title = get_input('title');
$entity->description = get_input('description');
$entity->page = $page;
$entity->access_id = (int) get_input('access_id');

if (!$entity->save()) {
	return elgg_error_response(elgg_echo('tour:action:save:error'));
}

$forward = elgg_normalize_url("admin/administer_utilities/tour/view?guid={$entity->guid}");

return elgg_ok_response('', elgg_echo('tour:action:save:success'), $forward);
