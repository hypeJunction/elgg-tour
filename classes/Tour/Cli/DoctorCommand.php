<?php

declare(strict_types=1);

namespace Tour\Cli;

use Elgg\Cli\Command;

/**
 * Post-migration data integrity checks for the tour plugin.
 *
 * Run with:
 *   php elgg-cli tour:doctor
 */
class DoctorCommand extends Command {

	/**
	 * @return void
	 */
	protected function configure(): void {
		$this->setName('tour:doctor');
		$this->setDescription('Post-migration data integrity checks for tour');
	}

	/**
	 * @return int Exit code (self::SUCCESS on clean run)
	 */
	protected function command(): int {
		$exitCode = self::SUCCESS;

		// Count object/tour_page entities
		$count_tour_page = (int) elgg_get_entities([
			'type' => 'object',
			'subtype' => 'tour_page',
			'count' => true,
		]);
		$this->emit("  object/tour_page: {$count_tour_page} entities");

		// Count object/tour_stop entities
		$count_tour_stop = (int) elgg_get_entities([
			'type' => 'object',
			'subtype' => 'tour_stop',
			'count' => true,
		]);
		$this->emit("  object/tour_stop: {$count_tour_stop} entities");

		if ($exitCode === self::SUCCESS) {
			$this->emit('<info>tour:doctor complete — no issues found</info>');
		} else {
			$this->emit('<error>tour:doctor found issues — review output above</error>');
		}

		return $exitCode;
	}

	/**
	 * Write a line to the output handle.
	 *
	 * @param string $message Message to emit
	 *
	 * @return void
	 */
	private function emit(string $message): void {
		$this->output->writeln($message);
	}
}
