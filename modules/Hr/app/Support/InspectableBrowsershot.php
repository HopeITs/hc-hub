<?php

namespace Modules\Hr\Support;

use Spatie\Browsershot\Browsershot;

/**
 * A Browsershot that will hand over the node command it is about to run.
 *
 * Browsershot keeps raw stdout/stderr and the exit code to itself whenever the
 * node process exits 0 — which is exactly the case that fails on the server, so
 * there is nothing left to look at. The export doctor builds the very same
 * command through this subclass and runs it itself, with all three captured.
 */
class InspectableBrowsershot extends Browsershot
{
    /**
     * @return array<int, string>|string
     */
    public function nodeCommand(?string $targetPath = null): array|string
    {
        return $this->getFullCommand($this->createPdfCommand($targetPath));
    }

    /**
     * @return array<string, string|false>|null
     */
    public function nodeEnvironment(): ?array
    {
        return $this->isWindows() ? $this->getWindowsEnv() : null;
    }

    public function targetsWindows(): bool
    {
        return $this->isWindows();
    }
}
