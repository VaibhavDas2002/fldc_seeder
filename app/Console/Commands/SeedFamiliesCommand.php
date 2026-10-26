<?php

namespace App\Console\Commands;

use App\Services\IndianDataService;
use Illuminate\Console\Command;

class SeedFamiliesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-families 
                            {count=50 : Number of families to seed (e.g. 10, 20, 25, 50, 75, 100, 150, 200, 250, 450, 500)}
                            {--district= : Optional LGD District Code}
                            {--area= : Optional area classification: Urban or Rural}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk seed realistic Indian origin families with random members into PostgreSQL tables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $district = $this->option('district') ? (int) $this->option('district') : null;
        $area = $this->option('area') ?: null;

        $this->info("🚀 Starting bulk insertion of {$count} Indian families into PostgreSQL...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $t0 = microtime(true);

        try {
            $result = IndianDataService::seedBatch($count, $district, $area, function (int $current) use ($bar) {
                $bar->setProgress($current);
            });

            $bar->finish();
            $this->newLine(2);

            $duration = round(microtime(true) - $t0, 2);

            $this->info("✨ Seeding completed successfully in {$duration}s!");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Families Seeded', $result['count']],
                    ['Total Members Added', $result['total_members']],
                    ['Annapurna Bhandar Recipients (Female 25-60)', $result['annapurna_recipients']],
                    ['Execution Time', "{$duration} seconds"],
                ]
            );

            if (! empty($result['families'])) {
                $this->info('Sample seeded families:');
                $rows = array_map(function ($f) {
                    return [
                        $f['family_id'],
                        $f['hof'],
                        $f['district'],
                        $f['location'],
                        $f['members_count'],
                    ];
                }, array_slice($result['families'], 0, 5));

                $this->table(
                    ['Family UUID', 'Head of Family', 'District', 'Location', 'Members'],
                    $rows
                );
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Seeding failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
