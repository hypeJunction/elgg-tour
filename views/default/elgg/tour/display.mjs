/**
 * Bind topbar "Help" link, fetch /tour/data, hand off to shepherd.js.
 *
 * /tour/data returns a JSON document with a `steps` array — each step has
 * the keys shepherd.js's Step config understands (id, title, text, attachTo).
 * The display module is a thin glue layer: no DOM mutation beyond what
 * shepherd.js drives, no jQuery dependency, no legacy globals.
 */
import elgg from 'elgg';

const siteUrl = elgg.get_site_url();
const page = window.location.href.replace(siteUrl, '').split('/')[0];
const link = document.getElementById('tour-start');

if (link) {
	link.addEventListener('click', async (e) => {
		e.preventDefault();

		const [Shepherd, response] = await Promise.all([
			import(`${siteUrl}mod/tour/vendors/shepherd/shepherd.mjs`).then(m => m.default),
			elgg.fetch({
				url: 'tour/data',
				data: { page: page },
			}),
		]);

		const config = typeof response === 'string' ? JSON.parse(response) : response;
		const steps = Array.isArray(config.steps) ? config.steps : [];

		if (steps.length === 0) {
			return;
		}

		const tour = new Shepherd.Tour({
			useModalOverlay: true,
			defaultStepOptions: {
				cancelIcon: { enabled: true },
				scrollTo: { behavior: 'smooth', block: 'center' },
				classes: 'elgg-tour-step',
			},
		});

		steps.forEach((step, index) => {
			tour.addStep({
				id: step.id || `step-${index}`,
				title: step.title,
				text: step.text,
				attachTo: step.attachTo || undefined,
				buttons: [
					...(index > 0 ? [{
						text: elgg.echo('previous'),
						action: () => tour.back(),
						classes: 'elgg-tour-btn elgg-tour-btn-secondary',
					}] : []),
					{
						text: index === steps.length - 1 ? elgg.echo('done') : elgg.echo('next'),
						action: () => index === steps.length - 1 ? tour.complete() : tour.next(),
						classes: 'elgg-tour-btn elgg-tour-btn-primary',
					},
				],
			});
		});

		tour.start();
	});
}
