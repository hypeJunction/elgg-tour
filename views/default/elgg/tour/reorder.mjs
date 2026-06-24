/**
 * Draggable info text reordering — admin tour view.
 */
import 'jquery';
import 'jquery-ui';
import elgg from 'elgg';

var guid = elgg.get_page_owner_guid();

/**
 * Request the server to save the current order
 *
 * @param {Object} e
 */
var reorderItems = function (e) {
	var guids = [];

	$('.elgg-item-object-tour_stop').each(function (k, el) {
		var id = $(this).attr('id');
		var item_guid = id.replace('elgg-object-', '');

		guids.push(item_guid);
	});

	elgg.action('tour_page/reorder', {
		data: {
			guid: guid,
			guids: guids,
		}
	});
};

$('.elgg-list-entity-tour').sortable({
	items: '.elgg-item-object-tour_stop',
	handle: '.elgg-icon-drag-arrow',
	forcePlaceholderSize: true,
	placeholder: 'elgg-widget-placeholder',
	opacity: 0.8,
	revert: 500,
	stop: reorderItems
});
