<?php

$entity = elgg_extract('entity', $vars);

$metadata = elgg_view_menu('entity', [
	'entity' => $vars['entity'],
	'handler' => 'tour_stop',
	'sort_by' => 'priority',
	'class' => 'elgg-menu-hz',
]);

$page = htmlspecialchars((string) $entity->page, ENT_QUOTES, 'UTF-8');
$target = htmlspecialchars((string) $entity->target, ENT_QUOTES, 'UTF-8');
$order = (int) $entity->order;
$content = "<ul><li>Page: {$page}</li><li>Target: {$target}</li><li>Order: {$order}</li></ul>";

$params = [
	'entity' => $entity,
	'title' => $entity->title,
	'subtitle' => $content,
	'metadata' => $metadata,
	'content' => $entity->description,
];
$params = $params + $vars;
$list_body = elgg_view('object/elements/summary', $params);

$drag_handle = elgg_view_icon('drag-arrow');

echo elgg_view_image_block($drag_handle, $list_body);
