<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Desk;

use App\Support\Desk\Panels;

/**
 * Add, remove, resize and reorder a set of Desk panels. Shared by the Desk
 * (pages::desk.index, saved on users.desk_layout) and the top bar's Panels
 * popup (desk.page-panels, saved per page on users.page_panels). The panels'
 * own menus call these through $wire.$parent, and desk-grid.js calls
 * arrangePanels() after a drag or resize, so both hosts answer to the same names.
 *
 * @property-read list<array{panel: string, size: string, cols: int, rows: int}> $layout
 * @property-read array<string, array{id: string, title: string, description: string, icon: string, size: string, register: string|null}> $library
 */
trait ArrangesPanels
{
    /**
     * Save a new arrangement; null puts the host back on its default.
     *
     * @param  list<array{panel: string, size: string, cols: int, rows: int}>|null  $layout
     */
    abstract protected function storeLayout(?array $layout): void;

    public function resizePanel(string $panel, string $size): void
    {
        if (! isset(Panels::Sizes[$size])) {
            return;
        }

        $panels = app(Panels::class);

        $this->storeLayout(array_map(fn (array $entry): array => $entry['panel'] === $panel ? $panels->sized($panel, $size, Panels::Sizes[$size], $entry['rows'] ?? null) : $entry, $this->layout));
    }

    /**
     * A drag or an edge pulled on the Desk (desk-grid.js): the whole
     * arrangement it previewed, in order, as [panel, cols, rows]. Ignored
     * unless it holds exactly the panels already placed; it is read back
     * through Panels::flush(), so rows stay full whatever arrives.
     *
     * @param  array<mixed>  $entries
     */
    public function arrangePanels(array $entries): void
    {
        $placed = array_column($this->layout, 'size', 'panel');
        $panels = app(Panels::class);
        $arranged = [];

        foreach ($entries as $entry) {
            $panel = is_array($entry) ? ($entry['panel'] ?? null) : null;

            if (! is_string($panel) || ! isset($placed[$panel]) || isset($arranged[$panel])) {
                return;
            }

            $arranged[$panel] = $panels->sized($panel, $placed[$panel], $entry['cols'] ?? null, $entry['rows'] ?? null);
        }

        if (count($arranged) === count($placed)) {
            $this->storeLayout(array_values($arranged));
        }
    }

    /**
     * The panel menu's Move to top / earlier / later: the cards trade
     * places, never shapes. Every slot keeps its size, so a move cannot
     * open a hole in the Desk.
     */
    public function movePanel(string $panel, int $position): void
    {
        $layout = $this->layout;
        $order = array_column($layout, 'panel');
        $from = array_search($panel, $order, true);

        if ($from === false) {
            return;
        }

        array_splice($order, $from, 1);
        array_splice($order, max(0, min($position, count($order))), 0, [$panel]);

        $panels = app(Panels::class);

        $this->storeLayout(array_map(fn (array $slot, string $id): array => $panels->sized($id, $slot['size'], $slot['cols'], $slot['rows']), $layout, $order));
    }

    public function removePanel(string $panel): void
    {
        $this->storeLayout(array_values(array_filter($this->layout, fn (array $entry): bool => $entry['panel'] !== $panel)));
    }

    public function addPanel(string $panel): void
    {
        $library = $this->library;

        if (! isset($library[$panel]) || in_array($panel, array_column($this->layout, 'panel'), true)) {
            return;
        }

        $this->storeLayout([...$this->layout, app(Panels::class)->sized($panel, $library[$panel]['size'])]);
    }

    /**
     * The Undo toast after a removal: put the panel back where it was, at
     * the size it had. Checked like addPanel, so only an available panel
     * that is not already on the desk comes back.
     */
    public function undoRemove(string $panel, int $position, string $size, ?int $cols = null, ?int $rows = null): void
    {
        $library = $this->library;

        if (! isset($library[$panel]) || in_array($panel, array_column($this->layout, 'panel'), true)) {
            return;
        }

        $layout = $this->layout;
        array_splice($layout, max(0, min($position, count($layout))), 0, [app(Panels::class)->sized($panel, isset(Panels::Sizes[$size]) ? $size : $library[$panel]['size'], $cols, $rows)]);
        $this->storeLayout(array_values($layout));
    }

    /**
     * "Done" in the Customize sheet: the whole edited arrangement in one
     * call, or null to go back to the default. Unknown, unavailable and
     * repeated panels and unknown sizes are dropped.
     *
     * @param  array<mixed>|null  $layout
     */
    public function saveLayout(?array $layout): void
    {
        if ($layout === null) {
            $this->storeLayout(null);

            return;
        }

        $library = $this->library;
        $kept = [];

        foreach ($layout as $entry) {
            $panel = is_array($entry) ? ($entry['panel'] ?? null) : null;
            $size = is_array($entry) ? ($entry['size'] ?? null) : null;

            if (! is_string($panel) || ! isset($library[$panel]) || isset($kept[$panel])) {
                continue;
            }

            $size = is_string($size) && isset(Panels::Sizes[$size]) ? $size : $library[$panel]['size'];
            $kept[$panel] = app(Panels::class)->sized($panel, $size, $entry['cols'] ?? null, $entry['rows'] ?? null);
        }

        $this->storeLayout(array_values($kept));
    }

    public function resetLayout(): void
    {
        $this->storeLayout(null);
    }
}
