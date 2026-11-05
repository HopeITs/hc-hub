<?php

declare(strict_types=1);

use App\Enums\AiRequestStatus;
use App\Models\AiRequest;
use App\Models\User;
use App\Support\Ai\AiGateway;
use App\Support\Ai\HcAgent;

/**
 * A throwaway agent: the gateway works with any agent, so tests use this one.
 */
class GatewayProbeAgent extends HcAgent
{
    public function instructions(): string
    {
        return 'Answer briefly.';
    }
}

beforeEach(function (): void {
    config([
        'ai.default' => 'ollama',
        'ai.models.default' => 'qwen3:8b',
        'ai.models.fast' => 'qwen3:4b',
    ]);
});

it('returns the agent answer and logs a succeeded call for the user', function (): void {
    GatewayProbeAgent::fake(['مرحبا']);
    $user = User::factory()->create();

    $response = app(AiGateway::class)->prompt(new GatewayProbeAgent, 'Say hello', 'probe', $user);

    expect($response->text)->toBe('مرحبا');

    $logged = AiRequest::sole();
    expect($logged->user_id)->toBe($user->id)
        ->and($logged->feature)->toBe('probe')
        ->and($logged->status)->toBe(AiRequestStatus::Succeeded)
        ->and($logged->error)->toBeNull();
});

it('uses the configured model for each tier', function (string $tier, string $model): void {
    GatewayProbeAgent::fake(['ok']);

    app(AiGateway::class)->prompt(new GatewayProbeAgent, 'Hi', 'probe', tier: $tier);

    expect(AiRequest::sole()->model)->toBe($model);
})->with([
    'drafting' => ['default', 'qwen3:8b'],
    'summaries' => ['fast', 'qwen3:4b'],
]);

it('logs a failed call and rethrows when the provider is down', function (): void {
    GatewayProbeAgent::fake(function (): never {
        throw new RuntimeException('Connection refused');
    });

    expect(fn () => app(AiGateway::class)->prompt(new GatewayProbeAgent, 'Hi', 'probe'))
        ->toThrow(RuntimeException::class, 'Connection refused');

    $logged = AiRequest::sole();
    expect($logged->status)->toBe(AiRequestStatus::Failed)
        ->and($logged->error)->toBe('Connection refused')
        ->and($logged->user_id)->toBeNull();
});

it('drops reasoning a local model leaked into its answer', function (string $reply, string $answer): void {
    GatewayProbeAgent::fake([$reply]);
    $gateway = app(AiGateway::class);

    expect($gateway->answerText($gateway->prompt(new GatewayProbeAgent, 'Hi', 'probe')))->toBe($answer);
})->with([
    'leaked reasoning' => ["Okay, the user wants a greeting...\n</think>\n\nمرحباً فريق العمل", 'مرحباً فريق العمل'],
    'clean answer' => ['  مرحباً فريق العمل ', 'مرحباً فريق العمل'],
]);

it('turns off Ollama thinking and keeps the model loaded', function (): void {
    config(['ai.provider_options.ollama' => ['think' => false, 'keep_alive' => '15m']]);

    expect((new GatewayProbeAgent)->providerOptions('ollama'))->toBe(['think' => false, 'keep_alive' => '15m'])
        ->and((new GatewayProbeAgent)->providerOptions('anthropic'))->toBe([]);
});
