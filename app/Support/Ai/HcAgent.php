<?php

declare(strict_types=1);

namespace App\Support\Ai;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * Base for every hc_tracking AI agent.
 *
 * Applies the per-provider options from config/ai.php (for Ollama: no hidden
 * "thinking" pass, and the model stays loaded between calls), so each feature
 * only writes its own instructions.
 */
abstract class HcAgent implements Agent, HasProviderOptions
{
    use Promptable;

    /**
     * @return array<string, mixed>
     */
    public function providerOptions(Lab|string $provider): array
    {
        $driver = $provider instanceof Lab ? $provider->value : $provider;

        return (array) config("ai.provider_options.{$driver}", []);
    }
}
