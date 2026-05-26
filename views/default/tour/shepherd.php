<?php
/**
 * Build the shepherd.js step configuration for the active tour page.
 *
 * Output shape (consumed by views/default/elgg/tour/display.mjs):
 *   {
 *     "steps": [
 *       {
 *         "id": "step-<guid>",
 *         "title": "Friendly title",
 *         "text": "<p>Step body (HTML)</p>",
 *         "attachTo": { "element": "#some-selector", "on": "bottom" }
 *       },
 *       ...
 *     ]
 *   }
 *
 * Legacy Tour\Stop data:
 *   $entity->target    — selector preceded by `#` (id) or `.` (class)
 *   $entity->placement — joyride-era position keyword (top/right/bottom/left)
 *
 * The selector is passed through verbatim; shepherd.js accepts any valid
 * CSS selector in `attachTo.element`.
 */

$entities = elgg_extract('stops', $vars);

$steps = [];

foreach ($entities as $entity) {
	$title_key = "tour:title:{$entity->guid}";
	$content_key = "tour:body:{$entity->guid}";

	$title = elgg_language_key_exists($title_key) ? elgg_echo($title_key) : $entity->title;
	$content = elgg_language_key_exists($content_key) ? elgg_echo($content_key) : $entity->description;

	$step = [
		'id' => "step-{$entity->guid}",
		'title' => $title,
		'text' => elgg_view('output/longtext', [
			'value' => $content,
		]),
	];

	if (!empty($entity->target)) {
		$step['attachTo'] = [
			'element' => $entity->target,
			'on' => $entity->placement ?: 'bottom',
		];
	}

	$steps[] = $step;
}

echo json_encode([
	'steps' => $steps,
]);
