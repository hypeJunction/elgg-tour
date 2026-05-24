<?php

$guid = get_input('guid');

$entity = get_entity($guid);

if (!$entity instanceof \Tour\Stop) {
	throw new \Elgg\Exceptions\Http\EntityNotFoundException(elgg_echo('tour:error:notfound'));
}

$form_helper = new \Tour\Stop\Form;
$form_vars = $form_helper->prepare($entity);

echo elgg_view_form('tour_stop/save', [], $form_vars);
