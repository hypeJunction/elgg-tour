<?php

$attrs = get_input('attrs');

$attr_options = [];
foreach ($attrs as $key => $value) {
	$attr_options[$value] = $value;
}

$form_vars = [
	'attrs' => $attr_options,
	'description' => null,
];

echo elgg_view_form('tour_stop/save', [], $form_vars);
