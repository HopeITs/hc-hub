<?php

namespace Tests\Feature\Recruitment;

use App\Enums\RecruitmentRequestStatus as Status;
use App\Enums\RecruitmentRequestType as Type;
use App\Livewire\Recruitment\Requests\Index;
use App\Livewire\Recruitment\Requests\RequestTable;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\AssertsOrderedColumnsExist;
use Tests\Concerns\BuildsWorkflowFixtures;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

/**
 * Finding one request among many.
 *
 * The tabs and the filters are two ways of narrowing the same scoped query,
 * and the thing that matters about both is that neither widens it: a count
 * that promises rows the viewer cannot open, or a filter option naming a
 * person outside their scope, leaks more than it helps.
 */
class RecruitmentRequestListTest extends TestCase
{
    use AssertsOrderedColumnsExist, BuildsWorkflowFixtures, CreatesHrFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');
    }

    /**
     * Each tab narrows the list; "all" is every one of them together.
     *
     * Asserted on the query the table builds rather than on the HTML it
     * renders. The rows arrive through the package's own lazy loading, and a
     * test that reads its markup would be testing the package rather than the
     * scoping this file is about.
     */
    public function test_the_tabs_filter_the_list_by_status(): void
    {
        [$hr, $request] = $this->seeded();

        $approved = $this->copy($request, Status::Approved);

        $this->assertSame(
            [$approved->id],
            $this->rows($hr, (string) Status::Approved),
        );

        $this->assertEqualsCanonicalizing(
            [$approved->id, $request->id],
            $this->rows($hr, 'all'),
        );
    }

    /** The counts are read through the same boundary as the rows. */
    public function test_the_counts_match_what_the_viewer_can_open(): void
    {
        [$hr, $request] = $this->seeded();
        $this->copy($request, Status::Approved);
        $this->copy($request, Status::Approved);

        $counts = Livewire::actingAs($hr)->test(Index::class)->viewData('counts');

        $this->assertSame(count($this->rows($hr, 'all')), $counts['all']);
        $this->assertSame(2, $counts[(string) Status::Approved]);
    }

    /** A tab is a filter, not a second list: it cannot show what "all" hides. */
    public function test_a_tab_never_widens_the_scope(): void
    {
        [$hr, $request] = $this->seeded();

        // A request in another governorate entirely. Built directly rather
        // than through the fixture, which would collide on the city slug.
        $elsewhere = $this->makeCity('LAT', (string) random_int(100, 899));
        $stranger = $this->copy($request, Status::UnderReview);
        $stranger->forceFill(['scope_city_id' => $elsewhere->id])->save();

        $this->assertNotContains($stranger->id, $this->rows($hr, 'all'));
        $this->assertNotContains($stranger->id, $this->rows($hr, (string) Status::UnderReview));
    }

    /**
     * Every column but the date offers a filter.
     *
     * The date is deliberately absent: a dropdown of every timestamp is one
     * entry per row, which sorts well and filters not at all.
     */
    public function test_every_column_but_the_date_can_be_filtered(): void
    {
        [$hr] = $this->seeded();

        $table = new RequestTable;
        $this->actingAs($hr);

        $names = collect((fn () => $this->filters())->call($table))
            ->map(fn ($filter) => $filter->code())
            ->all();

        foreach (['request_type', 'status', 'mode', 'request_number',
            'requester_user_id', 'scope_city_id', 'target_position_id'] as $column) {
            $this->assertContains($column, $names, "{$column} should be filterable.");
        }

        $this->assertNotContains('created_at', $names);
    }

    /** The list orders by columns that exist — sqlite would not say otherwise. */
    public function test_the_list_orders_only_by_columns_that_exist(): void
    {
        [$hr] = $this->seeded();

        $this->assertEveryOrderedColumnExists(function () use ($hr): void {
            Livewire::actingAs($hr)->test(Index::class)->assertOk();
            Livewire::actingAs($hr)->test(RequestTable::class)->assertOk();
        });
    }

    /** @return array{0: User, 1: RecruitmentRequest} */
    private function seeded(): array
    {
        ['hr' => $hr, 'request' => $request] = $this->workflow();

        return [$hr->fresh(), $request];
    }

    /** Another request just like it, in a chosen status. */
    private function copy(RecruitmentRequest $request, int $status): RecruitmentRequest
    {
        return RecruitmentRequest::query()->create([
            'request_number' => 'ZZ-'.random_int(100, 999).'-'.uniqid(),
            'requester_user_id' => $request->requester_user_id,
            'request_type' => Type::FillVacancy,
            'mode' => $request->mode,
            'status' => $status,
            'scope_city_id' => $request->scope_city_id,
            'target_position_id' => $request->target_position_id,
        ]);
    }

    /**
     * The request ids the table would list under a given tab.
     *
     * @return array<int, int>
     */
    private function rows(User $viewer, string $statusFilter): array
    {
        $this->actingAs($viewer);

        $table = new RequestTable;
        $table->statusFilter = $statusFilter;

        return (fn () => $this->query())->call($table)->pluck('id')
            ->map(fn ($id): int => (int) $id)->all();
    }
}
