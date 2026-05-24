<?php

$guid = get_input('guid');

$entity = get_entity($guid);

if (!$entity instanceof \Tour\Page) {
	return elgg_error_response(elgg_echo('tour:error:page_not_found'));
}

if (!$entity->canEdit()) {
	return elgg_error_response(elgg_echo('actionunauthorized'));
}

if (!$entity->delete()) {
	return elgg_error_response(elgg_echo('tour:action:delete:error'));
}

return elgg_ok_response('', elgg_echo('tour:action:delete:success'));
