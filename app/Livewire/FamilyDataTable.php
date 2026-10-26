<?php

namespace App\Livewire;

use App\Models\Family;
use App\Services\IndianDataService;
use App\Services\MasterDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class FamilyDataTable extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'dist')]
    public string $districtFilter = '';

    #[Url(as: 'area')]
    public string $areaTypeFilter = '';

    #[Url(as: 'annapurna')]
    public string $annapurnaFilter = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public int $perPage = 10;

    public int $bulkSeedCount = 10;

    /**
     * Set of expanded family IDs to view members inline.
     *
     * @var array<string, bool>
     */
    public array $expandedFamilies = [];

    public ?string $viewingFamilyId = null;

    public ?array $viewingFamilyDetails = null;

    public string $feedbackMessage = '';

    public string $feedbackType = 'info';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDistrictFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAreaTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAnnapurnaFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleExpand(string $familyId): void
    {
        if (isset($this->expandedFamilies[$familyId])) {
            unset($this->expandedFamilies[$familyId]);
        } else {
            $this->expandedFamilies[$familyId] = true;
        }
    }

    public function expandAll(array $familyIds): void
    {
        foreach ($familyIds as $id) {
            $this->expandedFamilies[$id] = true;
        }
    }

    public function collapseAll(): void
    {
        $this->expandedFamilies = [];
    }

    public function viewFamily(string $familyId): void
    {
        $family = Family::with('members')->find($familyId);
        if ($family) {
            $this->viewingFamilyId = $familyId;
            $this->viewingFamilyDetails = $family->toArray();
        }
    }

    public function closeModal(): void
    {
        $this->viewingFamilyId = null;
        $this->viewingFamilyDetails = null;
    }

    public function deleteFamily(string $familyId, int $districtCode): void
    {
        try {
            DB::transaction(function () use ($familyId, $districtCode) {
                DB::table('dbt_apy.family_members')
                    ->where('family_id', $familyId)
                    ->where('lgd_district_code', $districtCode)
                    ->delete();

                DB::table('dbt_apy.families')
                    ->where('id', $familyId)
                    ->where('lgd_district_code', $districtCode)
                    ->delete();
            });

            unset($this->expandedFamilies[$familyId]);
            $this->feedbackMessage = "Family {$familyId} deleted successfully.";
            $this->feedbackType = 'success';
        } catch (\Exception $e) {
            $this->feedbackMessage = "Delete failed: {$e->getMessage()}";
            $this->feedbackType = 'error';
        }
    }

    public function quickSeedSample(): void
    {
        $this->bulkSeed(1);
    }

    /**
     * Bulk seed Indian families with random members directly into PostgreSQL.
     * Supports presets: 10, 20, 25, 50, 75, 100, 150, 200, 250, 450, 500 up to 1000.
     */
    public function bulkSeed(?int $count = null): void
    {
        $targetCount = $count ?? $this->bulkSeedCount;
        $targetCount = max(1, min($targetCount, 1000));

        $district = ! empty($this->districtFilter) ? (int) $this->districtFilter : null;
        $area = ! empty($this->areaTypeFilter) ? $this->areaTypeFilter : null;

        try {
            $result = IndianDataService::seedBatch($targetCount, $district, $area);
            $this->feedbackMessage = "⚡ Bulk seeded {$result['count']} Indian families ({$result['total_members']} members) into PostgreSQL!";
            $this->feedbackType = 'success';

            // Auto-expand the first seeded family if available
            if (! empty($result['families'][0]['family_id'])) {
                $this->expandedFamilies[$result['families'][0]['family_id']] = true;
            }
        } catch (\Exception $e) {
            $this->feedbackMessage = "Bulk seed failed: {$e->getMessage()}";
            $this->feedbackType = 'error';
        }
    }

    public function render(): View
    {
        $query = Family::query()->with('members');

        if (! empty($this->search)) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('address', 'ILIKE', $term)
                    ->orWhere('ration_card_household_id', 'ILIKE', $term)
                    ->orWhereRaw('id::text ILIKE ?', [$term])
                    ->orWhereRaw('tmp_application_id::text ILIKE ?', [$term])
                    ->orWhereHas('members', function ($mq) use ($term) {
                        $mq->where('member_name', 'ILIKE', $term)
                            ->orWhere('aadhaar_no', 'ILIKE', $term)
                            ->orWhere('mobile_no', 'ILIKE', $term)
                            ->orWhere('epic_no', 'ILIKE', $term);
                    });
            });
        }

        if (! empty($this->districtFilter)) {
            $query->where('lgd_district_code', (int) $this->districtFilter);
        }

        if (! empty($this->areaTypeFilter)) {
            $query->where('area_type', $this->areaTypeFilter);
        }

        if ($this->annapurnaFilter === 'eligible_only') {
            $query->whereHas('members', function ($q) {
                $q->where('applying_for_annapurna_bhandar', true);
            });
        } elseif ($this->annapurnaFilter === 'none') {
            $query->whereDoesntHave('members', function ($q) {
                $q->where('applying_for_annapurna_bhandar', true);
            });
        }

        $allowedSorts = ['created_at', 'total_family_members', 'lgd_district_code', 'total_annual_family_income'];
        $sortField = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'created_at';
        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        $families = $query->orderBy($sortField, $sortDirection)->paginate($this->perPage);

        // Overall statistics
        $stats = [
            'total_families' => DB::table('dbt_apy.families')->count(),
            'total_members' => DB::table('dbt_apy.family_members')->count(),
            'annapurna_count' => DB::table('dbt_apy.family_members')
                ->where('applying_for_annapurna_bhandar', true)
                ->count(),
        ];

        $districts = MasterDataService::getDistricts();

        return view('livewire.family-data-table', [
            'families' => $families,
            'stats' => $stats,
            'districts' => $districts,
        ]);
    }
}
