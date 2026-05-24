<?php

$library_label = elgg_echo('tour:setting:library');
$library_desc = elgg_echo('tour:setting:library:desc');
$library_input = elgg_view('input/dropdown', [
	'name' => 'params[js_library]',
	'options_values' => [
		'hopscotch' => 'Hopscotch',
		'joyride' => 'Joyride',
	],
	'value' => $vars['entity']->js_library,
]);

?>
<div>
	<label>
		<?= $library_label ?>
		<?= $library_input ?>
	</label>
	<div><?= $library_desc ?></div>
</div>
