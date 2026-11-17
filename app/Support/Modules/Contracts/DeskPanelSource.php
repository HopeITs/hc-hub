<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use Closure;
use Modules\Core\Models\User;

/**
 * A module's Desk panels for App\Support\Desk\Panels: placed, sized, pinned,
 * folded and removed like the platform's own, but defined, permitted and
 * drawn by the module. Registered with ModuleRegistry::addDeskPanels().
 */
interface DeskPanelSource
{
    /**
     * Whether a Desk id is one of this module's panels (the module's id prefix).
     */
    public function owns(string $id): bool;

    /**
     * The Livewire component that draws one of this module's panels.
     */
    public function component(string $id): string;

    /**
     * The group the module's panels sit in on the Customize sheet's Add list.
     */
    public function kind(): string;

    /**
     * The module's panels as Panels definitions, by Desk id.
     *
     * @return array<string, array{icon: string, size: string, rows: int, register: string|null, allows: Closure(User): bool, title: string, description: string, locked: string, kind: string}>
     */
    public function definitions(): array;

    /**
     * The panel that stands for a page when it is dragged onto the Desk (route => panel).
     *
     * @return array<string, string>
     */
    public function routePanels(): array;

    /**
     * Starting Desks for the module's own roles (role => [[panel, size], …]),
     * read after the platform's role layouts so those win for a person holding both.
     *
     * @return array<string, list<array{0: string, 1: string}>>
     */
    public function roleLayouts(): array;
}
