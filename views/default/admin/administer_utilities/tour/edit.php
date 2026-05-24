<?php

$guid = get_input('guid');

$entity = get_entity($guid);

if (!$entity instanceof \Tour\Page) {
	throw new \Elgg\Exceptions\Http\EntityNotFoundException(elgg_echo('tour:error:page_not_found'));
}

$form_helper = new \Tour\Page\Form;
$form_vars = $form_helper->prepare($entity);

echo elgg_view_form('tour_page/save', [], $form_vars);
