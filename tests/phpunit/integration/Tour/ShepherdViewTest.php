<?php

namespace Tour;

use Elgg\IntegrationTestCase;

/**
 * Behavioural coverage for the shepherd.js JSON emitter (migration ea40cbc) and the
 * /tour/data resource that feeds it.
 */
class ShepherdViewTest extends IntegrationTestCase {

	public function up() {}

	public function down() {}

	public function getPluginID(): string {
		return 'tour';
	}

	/**
	 * Regression ea40cbc: tour/shepherd emits {steps:[{id,title,text,attachTo?}]}. attachTo
	 * is present only when the stop has a target, and its `on` defaults to 'bottom' when the
	 * placement is empty.
	 */
	public function testShepherdViewEmitsStepsJson(): void {
		$attached = $this->createObject([
			'subtype' => 'tour_stop',
			'title' => 'Attached Step',
			'description' => 'Attached body',
			'access_id' => ACCESS_PUBLIC,
		]);
		$attached->target = '#sidebar';
		$attached->placement = 'right';

		$defaulted = $this->createObject([
			'subtype' => 'tour_stop',
			'title' => 'Defaulted Step',
			'description' => 'Defaulted body',
			'access_id' => ACCESS_PUBLIC,
		]);
		$defaulted->target = '#topbar';
		// no placement -> 'on' should default to 'bottom'

		$plain = $this->createObject([
			'subtype' => 'tour_stop',
			'title' => 'Plain Step',
			'description' => 'Plain body',
			'access_id' => ACCESS_PUBLIC,
		]);
		// no target -> no attachTo

		$output = elgg_view('tour/shepherd', ['stops' => [$attached, $defaulted, $plain]]);
		$data = json_decode($output, true);

		$this->assertIsArray($data);
		$this->assertArrayHasKey('steps', $data);
		$this->assertCount(3, $data['steps']);

		$this->assertSame("step-{$attached->guid}", $data['steps'][0]['id']);
		$this->assertSame('Attached Step', $data['steps'][0]['title']);
		$this->assertArrayHasKey('text', $data['steps'][0]);
		$this->assertSame('#sidebar', $data['steps'][0]['attachTo']['element']);
		$this->assertSame('right', $data['steps'][0]['attachTo']['on']);

		$this->assertSame('#topbar', $data['steps'][1]['attachTo']['element']);
		$this->assertSame('bottom', $data['steps'][1]['attachTo']['on'], 'empty placement must default to bottom');

		$this->assertArrayNotHasKey('attachTo', $data['steps'][2], 'no target => no attachTo');
	}

	/**
	 * resources/tour/data must resolve the tour_page whose `page` metadata matches the input,
	 * scope to that page's children, and order them by the `order` metadata (ASC, integer).
	 */
	public function testTourDataResourceScopesToPageAndOrdersStops(): void {
		$page = $this->createObject(['subtype' => 'tour_page', 'access_id' => ACCESS_PUBLIC]);
		$page->page = '/dashboard';

		$other = $this->createObject(['subtype' => 'tour_page', 'access_id' => ACCESS_PUBLIC]);
		$other->page = '/other';

		$second = $this->createObject(['subtype' => 'tour_stop', 'title' => 'Second', 'access_id' => ACCESS_PUBLIC]);
		$second->container_guid = $page->guid;
		$second->order = 5;
		$second->save();

		$first = $this->createObject(['subtype' => 'tour_stop', 'title' => 'First', 'access_id' => ACCESS_PUBLIC]);
		$first->container_guid = $page->guid;
		$first->order = 1;
		$first->save();

		// belongs to a different page — must be excluded
		$foreign = $this->createObject(['subtype' => 'tour_stop', 'title' => 'Foreign', 'access_id' => ACCESS_PUBLIC]);
		$foreign->container_guid = $other->guid;
		$foreign->order = 1;
		$foreign->save();

		set_input('page', '/dashboard');
		$data = json_decode(elgg_view('resources/tour/data'), true);

		$this->assertIsArray($data);
		$this->assertArrayHasKey('steps', $data);
		$this->assertCount(2, $data['steps'], 'only the matching page children are returned');
		$this->assertSame("step-{$first->guid}", $data['steps'][0]['id'], 'order=1 must come first');
		$this->assertSame("step-{$second->guid}", $data['steps'][1]['id'], 'order=5 must come second');
	}
}
