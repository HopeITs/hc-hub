<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Modules\Hr\Support\MailLists\MailListImporter;

/**
 * Reloads the organisation's mailing lists from the data file: each list in the file replaces its
 * members, new lists are created, lists the file does not name are left alone.
 */
class LoadMailListsCommand extends Command
{
    protected $signature = 'mail-lists:load {path? : A JSON file of lists (default modules/Hr/database/data/mail-lists.json)}';

    protected $description = 'Load the organisation mailing lists from the data file';

    public function handle(MailListImporter $importer): int
    {
        $path = (string) ($this->argument('path') ?? MailListImporter::defaultPath());

        if (! is_file($path)) {
            $this->components->error("No such file: {$path}");

            return self::FAILURE;
        }

        $result = $importer->importFile($path);

        $this->components->info("Loaded {$result['lists']} lists with {$result['members']} members.");

        return self::SUCCESS;
    }
}
