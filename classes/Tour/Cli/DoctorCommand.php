<?php

declare(strict_types=1);

namespace Tour\Cli;

use Elgg\Cli\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Post-migration data integrity checks for the tour plugin.
 *
 * Run with:
 *   php elgg-cli tour:doctor
 */
class DoctorCommand extends Command {

    /** @var mixed */
    protected static $defaultName = 'tour:doctor';

    /**
     * @return void
     */
    protected function configure(): void {
        $this->setDescription('Post-migration data integrity checks for tour');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function command(InputInterface $input, OutputInterface $output): int {
        $exitCode = self::SUCCESS;

        // Count object/tour_page entities
        $count_tour_page = (int) elgg_get_entities([
            'type' => 'object',
            'subtype' => 'tour_page',
            'count' => true,
        ]);
        $output->writeln("  object/tour_page: {$count_tour_page} entities");

        // Count object/tour_stop entities
        $count_tour_stop = (int) elgg_get_entities([
            'type' => 'object',
            'subtype' => 'tour_stop',
            'count' => true,
        ]);
        $output->writeln("  object/tour_stop: {$count_tour_stop} entities");

        // Verify upgrades completed
        // TODO: check pending Elgg\Upgrade\Batch scripts for this plugin
        // Example: query elgg_entities for type='object' subtype='upgrade' with status != 'completed'

        // Orphan relationship check
        // TODO: check for relationships referencing non-existent entities owned by this plugin

        // Plugin-specific config invariants
        // TODO: verify expected plugin settings are set and valid

        if ($exitCode === self::SUCCESS) {
            $output->writeln('<info>tour:doctor complete — no issues found</info>');
        } else {
            $output->writeln('<error>tour:doctor found issues — review output above</error>');
        }

        return $exitCode;
    }
}