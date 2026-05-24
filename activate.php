<?php
/**
 * Register classes
 */

if (get_subtype_id('object', \Tour\Stop::SUBTYPE)) {
	elgg_set_entity_class('object', \Tour\Stop::SUBTYPE, 'Tour\Stop');
} else {
	elgg_set_entity_class('object', \Tour\Stop::SUBTYPE, 'Tour\Stop');
}

if (get_subtype_id('object', \Tour\Page::SUBTYPE)) {
	elgg_set_entity_class('object', \Tour\Page::SUBTYPE, 'Tour\Page');
} else {
	elgg_set_entity_class('object', \Tour\Page::SUBTYPE, 'Tour\Page');
}
