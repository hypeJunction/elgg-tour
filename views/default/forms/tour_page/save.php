<?php
/**
 * Edit form for tour content.
 */

$page_label = elgg_echo('tour:page');
$page_input = elgg_view('input/text', [
	'name' => 'page',
	'value' => $vars['page'],
]);

$title_label = elgg_echo('title');
$title_input = elgg_view('input/text', [
	'name' => 'title',
	'value' => $vars['title'],
]);

$desc_label = elgg_echo('description');
$desc_input = elgg_view('input/longtext', [
	'name' => 'description',
	'value' => $vars['description'],
]);

$context_input = elgg_view('input/radio', [
	'name' => 'context',
	'options' => [
		elgg_echo('tour:context:1') => 'all',
		elgg_echo('tour:context:2') => 'current',
		elgg_echo('tour:context:3') => 'current_and_subpages',
		elgg_echo('tour:context:4') => 'current_and_all_subpages',
	],
]);

$access_label = elgg_echo('access');
$access_input = elgg_view('input/access', [
	'name' => 'access_id',
	'value' => $vars['access_id'],
]);

$guid_input = elgg_view('input/hidden', [
	'name' => 'guid',
	'value' => $vars['guid'],
]);

$submit_input = elgg_view('input/submit', [
	'value' => elgg_echo('save'),
]);

?>
<div>
	<label><?= $page_label ?></label>
	<?= $page_input ?>
</div>
<div>
	<?= $context_input ?>
</div>
<div>
	<label><?= $title_label ?></label>
	<?= $title_input ?>
</div>
<div>
	<label><?= $desc_label ?></label>
	<?= $desc_input ?>
</div>
<div>
	<label><?= $access_label ?></label>
	<?= $access_input ?>
</div>
<div>
	<?= $guid_input ?>
	<?= $submit_input ?>
</div>
