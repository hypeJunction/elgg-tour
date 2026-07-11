<?php

namespace Tour;

use Elgg\IntegrationTestCase;

/**
 * The tour is logged-in onboarding: elgg/tour/display and the shepherd CSS were
 * imported on every page, including anonymous ones where the #tour-start topbar
 * trigger never renders (bd elgg-migrate-xhigk). Bootstrap::registerTourForUsers()
 * now gates the whole tour UI on an authenticated session.
 */
class TourEsmTest extends IntegrationTestCase {

	public function up(): void {
		_elgg_services()->session_manager->removeLoggedInUser();
	}

	public function down(): void {
		_elgg_services()->session_manager->removeLoggedInUser();
	}

	public function testTourModuleNotImportedForAnonymous(): void {
		_elgg_services()->session_manager->removeLoggedInUser();
		$this->assertFalse(elgg_is_logged_in());

		Bootstrap::registerTourForUsers();

		$this->assertNotContains(
			'elgg/tour/display',
			_elgg_services()->esm->getImports(),
			'the tour module must not load on anonymous pages'
		);
	}

	public function testTourModuleImportedForLoggedInUser(): void {
		$user = $this->createUser();
		_elgg_services()->session_manager->setLoggedInUser($user);

		Bootstrap::registerTourForUsers();

		$this->assertContains(
			'elgg/tour/display',
			_elgg_services()->esm->getImports(),
			'the tour module must load for an authenticated user'
		);

		_elgg_services()->session_manager->removeLoggedInUser();
	}
}
