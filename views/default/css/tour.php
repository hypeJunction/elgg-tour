
/*
 * Tour overrides on top of shepherd.css.
 *
 * shepherd.js ships with sensible defaults; we only nudge the z-index up so
 * shepherd's modal overlay sits above Elgg's topbar / sticky nav, and widen
 * the step box modestly to match the legacy 500px joyride look.
 */

.shepherd-modal-overlay-container {
	z-index: 11000 !important;
}

.shepherd-element {
	z-index: 12000 !important;
	max-width: 500px;
}

.elgg-tour-step .shepherd-button {
	margin-right: 0.25rem;
}
