<?php

$entity = elgg_extract('entity', $vars);

$metadata = elgg_view_menu('entity', [
	'entity' => $vars['entity'],
	'handler' => 'tour_page',
	'sort_by' => 'priority',
	'class' => 'elgg-menu-hz',
]);

$page = htmlspecialchars((string) $entity->page, ENT_QUOTES, 'UTF-8');
$content = "<ul><li>Page: {$page}</li></ul>";

$title = elgg_view('output/url', [
	'text' => $entity->title,
	'href' => $entity->getURL(),
]);

$params = [
	'entity' => $entity,
	'title' => $title,
	'subtitle' => $content,
	'metadata' => $metadata,
	'content' => $entity->description,
];
$params = $params + $vars;
$list_body = elgg_view('object/elements/summary', $params);

echo elgg_view_image_block('', $list_body);
