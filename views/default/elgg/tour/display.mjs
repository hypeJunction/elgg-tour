/**
 * Bind topbar "Help" link, fetch /tour/data, hand off to the configured library.
 *
 * The configured client library (Hopscotch or Joyride) is loaded site-wide
 * as a global script (window.hopscotch or jQuery .joyride()) by the plugin
 * Bootstrap, so we rely on the global existing at click time.
 */
import 'jquery';
import elgg from 'elgg';

var link = $('#tour-start');
var library = link.attr('data-library');

// Remove site URL to get the path
var path = window.location.href.replace(elgg.get_site_url(), '');

// Get the first page segment of the path
var page = path.split('/')[0];

link.on('click', function (e) {
	e.preventDefault();

	elgg.get({
		url: 'tour/data',
		data: {'page': page},
		success: function (data) {
			if (library === 'hopscotch') {
				var json = JSON.parse(data);

				window.hopscotch.startTour(json);
			} else {
				$('body').append(data);

				$('#tour-outline').joyride({
					autoStart: true,
					modal: true,
					expose: true
				});
			}
		}
	});
});
