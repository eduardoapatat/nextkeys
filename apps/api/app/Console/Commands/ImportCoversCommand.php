<?php

namespace App\Console\Commands;

use App\Actions\Catalog\ImportCoverArt;
use App\Catalog\CoverArt\CoverArtUnavailable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('catalog:import-covers {--force : Replace covers that are already set} {--title= : Only this title (exact name)}')]
#[Description('Download the official cover of each title from IGDB and set it on its products')]
class ImportCoversCommand extends Command
{
    public function handle(ImportCoverArt $import): int
    {
        if (! $import->isAvailable()) {
            $this->warn('IGDB is not configured: set IGDB_CLIENT_ID and IGDB_CLIENT_SECRET in .env (a Twitch application). No covers were imported.');

            return self::SUCCESS;
        }

        $title = $this->option('title');

        try {
            $outcomes = $import->handle(
                force: (bool) $this->option('force'),
                title: is_string($title) && $title !== '' ? $title : null,
            );
        } catch (CoverArtUnavailable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($outcomes === []) {
            $this->info('Every product already has a cover. Use --force to replace them.');

            return self::SUCCESS;
        }

        foreach ($outcomes as $name => $outcome) {
            $outcome === 'imported'
                ? $this->info("{$name}: cover imported.")
                : $this->warn("{$name}: not found on IGDB; upload its cover from the panel.");
        }

        return self::SUCCESS;
    }
}
