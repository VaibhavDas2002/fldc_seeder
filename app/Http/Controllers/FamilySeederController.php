<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Services\AadhaarService;
use App\Services\IndianDataService;
use App\Services\MasterDataService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FamilySeederController extends Controller
{
    /**
     * Display the seeder dashboard and form.
     */
    public function index(): View
    {
        $stats = [
            'total_families' => DB::table('dbt_apy.families')->count(),
            'total_members' => DB::table('dbt_apy.family_members')->count(),
            'annapurna_recipients' => DB::table('dbt_apy.family_members')
                ->where('applying_for_annapurna_bhandar', true)
                ->count(),
            'districts_count' => DB::table('dbt_apy.families')
                ->distinct('lgd_district_code')
                ->count('lgd_district_code'),
        ];

        $districts = MasterDataService::getDistricts();
        $masterData = MasterDataService::getMasterData();

        $recentFamilies = DB::table('dbt_apy.families')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('seeder', [
            'stats' => $stats,
            'districts' => $districts,
            'masterData' => $masterData,
            'recentFamilies' => $recentFamilies,
        ]);
    }

    /**
     * Store a newly created family and its members.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lgd_district_code' => ['required', 'integer'],
            'lgd_block_mc_code' => ['required', 'integer'],
            'lgd_gp_ward_code' => ['required', 'integer'],
            'area_type' => ['required', 'string'],
            'ulb' => ['nullable', 'integer'],
            'address' => ['nullable', 'string'],
            'has_digital_ration_card' => ['nullable'],
            'ration_card_household_id' => ['nullable', 'string'],
            'lifting_monthly_ration' => ['nullable'],
            'total_annual_family_income' => ['nullable', 'integer'],
            'no_of_literate_adults' => ['nullable', 'integer'],
            'no_of_illiterate_adults' => ['nullable', 'integer'],
            'is_agreed' => ['nullable'],
            'members' => ['required', 'array', 'min:1'],
            'members.*.member_name' => ['required', 'string'],
            'members.*.gender' => ['required', 'string'],
            'members.*.date_of_birth' => ['nullable', 'string'],
            'members.*.relation_with_head_of_family' => ['nullable', 'string'],
            'members.*.aadhaar_no' => ['required', 'string'],
            'members.*.mobile_no' => ['nullable', 'string'],
            'members.*.is_hof' => ['nullable'],
            'members.*.social_category' => ['nullable', 'string'],
            'members.*.religion' => ['nullable', 'string'],
            'members.*.bank_name' => ['nullable', 'string'],
            'members.*.bank_account_no' => ['nullable', 'string'],
            'members.*.ifsc_code' => ['nullable', 'string'],
            'members.*.epic_no' => ['nullable', 'string'],
            'members.*.has_health_insurance' => ['nullable'],
            'members.*.health_insurance_type' => ['nullable', 'string'],
            'members.*.has_four_wheeler' => ['nullable'],
            'members.*.pays_income_or_professional_tax' => ['nullable'],
            'members.*.is_registered_gst' => ['nullable'],
            'members.*.holds_constitutional_post' => ['nullable'],
        ]);

        try {
            $result = DB::transaction(function () use ($validated) {
                // Generate family UUID
                $familyUuid = DB::select('SELECT dbt_apy.generate_zone_uuid(1) as uuid')[0]->uuid;
                $tmpAppUuid = DB::select('SELECT dbt_apy.generate_zone_uuid(2) as uuid')[0]->uuid;

                $districtCode = (int) $validated['lgd_district_code'];
                $blockMcCode = (int) $validated['lgd_block_mc_code'];
                $gpWardCode = (int) $validated['lgd_gp_ward_code'];
                $areaType = $validated['area_type'] ?? 'Urban';
                $ulb = ! empty($validated['ulb']) ? (int) $validated['ulb'] : ($areaType === 'Urban' ? $blockMcCode : null);

                $totalMembers = count($validated['members']);
                $hasRc = ! empty($validated['has_digital_ration_card']);
                $liftingRation = ! empty($validated['lifting_monthly_ration']);
                $isAgreed = true; // Required by DB constraint

                // Insert Family Master
                DB::table('dbt_apy.families')->insert([
                    'id' => $familyUuid,
                    'tmp_application_id' => $tmpAppUuid,
                    'total_family_members' => $totalMembers,
                    'lifting_monthly_ration' => $liftingRation,
                    'has_digital_ration_card' => $hasRc,
                    'ration_card_household_id' => $validated['ration_card_household_id'] ?? null,
                    'no_of_illiterate_adults' => $validated['no_of_illiterate_adults'] ?? 0,
                    'no_of_literate_adults' => $validated['no_of_literate_adults'] ?? $totalMembers,
                    'total_annual_family_income' => $validated['total_annual_family_income'] ?? 120000,
                    'address' => $validated['address'] ?? 'Not Specified',
                    'lgd_district_code' => $districtCode,
                    'lgd_block_mc_code' => $blockMcCode,
                    'lgd_gp_ward_code' => $gpWardCode,
                    'area_type' => $areaType,
                    'ulb' => $ulb,
                    'is_agreed' => $isAgreed,
                    'application_status' => 'DRAFT',
                    'create_mode' => 'o',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'kafka_updation_timestamp' => now(),
                    'under_enquiry' => false,
                    'transfer_out' => false,
                ]);

                // Insert Family Members
                $hofId = null;
                $memberUuids = [];
                $annapurnaBeneficiaries = [];

                foreach ($validated['members'] as $idx => $m) {
                    $memberUuid = DB::select('SELECT dbt_apy.generate_zone_uuid(3) as uuid')[0]->uuid;
                    $isHof = ! empty($m['is_hof']) || ($idx === 0 && empty(array_filter($validated['members'], fn ($x) => ! empty($x['is_hof']))));

                    if ($isHof && $hofId === null) {
                        $hofId = $memberUuid;
                    }

                    $gender = ucfirst(strtolower(trim($m['gender'] ?? 'Female')));
                    if ($gender === 'F') {
                        $gender = 'Female';
                    }
                    if ($gender === 'M') {
                        $gender = 'Male';
                    }

                    // Calculate age from DOB
                    $dob = $m['date_of_birth'] ?? null;
                    $age = 30; // default age if not provided
                    if (! empty($dob)) {
                        try {
                            $age = Carbon::parse($dob)->age;
                        } catch (\Exception $e) {
                            $age = 30;
                        }
                    }

                    // Strict Annapurna rule:
                    // "family may contain a female member . if it is and the age is between 25-60 years then the column 'applying_for_annapurna_bhandar' be true"
                    $isAnnapurnaEligible = ($gender === 'Female' && $age >= 25 && $age <= 60);

                    if ($isAnnapurnaEligible) {
                        $annapurnaBeneficiaries[] = $m['member_name'];
                    }

                    $isChild = ($age < 18);

                    $memberData = [
                        'id' => $memberUuid,
                        'family_id' => $familyUuid,
                        'lgd_district_code' => $districtCode,
                        'lgd_block_mc_code' => $blockMcCode,
                        'lgd_gp_ward_code' => $gpWardCode,
                        'is_hof' => $isHof,
                        'member_name' => $m['member_name'],
                        'date_of_birth' => $dob,
                        'gender' => $gender,
                        'relation_with_head_of_family' => $isHof ? 'Self' : ($m['relation_with_head_of_family'] ?? 'Spouse'),
                        'aadhaar_no' => preg_replace('/\D/', '', $m['aadhaar_no']),
                        'mobile_no' => $m['mobile_no'] ?? null,
                        'applying_for_annapurna_bhandar' => $isAnnapurnaEligible,
                        'bank_name' => $m['bank_name'] ?? null,
                        'bank_account_no' => $m['bank_account_no'] ?? null,
                        'ifsc_code' => $m['ifsc_code'] ?? null,
                        'epic_no' => $m['epic_no'] ?? null,
                        'social_category' => $m['social_category'] ?? 'General',
                        'religion' => $m['religion'] ?? 'Hinduism',
                        'has_digital_ration_card' => $hasRc,
                        'lifting_monthly_ration' => $liftingRation,
                        'has_health_insurance' => ! empty($m['has_health_insurance']),
                        'health_insurance_type' => $m['health_insurance_type'] ?? 'Swasthya Sathi',
                        'pays_income_or_professional_tax' => ! empty($m['pays_income_or_professional_tax']),
                        'is_registered_gst' => ! empty($m['is_registered_gst']),
                        'holds_constitutional_post' => ! empty($m['holds_constitutional_post']),
                        'is_govt_pensioner' => false,
                        'has_four_wheeler' => ! empty($m['has_four_wheeler']),
                        'is_child' => $isChild,
                        'head_of_family_id' => $hofId ?? $memberUuid,
                        'application_status' => 'DRAFT',
                        'create_mode' => 'o',
                        'created_at' => now(),
                        'updated_at' => now(),
                        'kafka_updation_timestamp' => now(),
                        'under_enquiry' => false,
                        'july_payment' => false,
                        'govt_self_decl' => false,
                        'govt_fd' => false,
                        'govt_wbhs' => false,
                        'govt_asha' => false,
                        'govt_icds' => false,
                        'three_room_urb' => false,
                        'land_mu_corp' => false,
                        'oth_madrassah' => false,
                        'not_in_asdd_fd' => true,
                        'lb_ben_sir' => false,
                        'govt_fd_not_permanent' => false,
                        'govt_fd_sch' => false,
                        'govt_fd_gpzp' => false,
                        'is_excluded' => false,
                        'aug_prep' => false,
                        'priority_add' => false,
                        'priority_del' => false,
                        'transfer_out' => false,
                        'is_oap' => false,
                        'jm_status' => false,
                        'sep_priority' => false,
                    ];

                    DB::table('dbt_apy.family_members')->insert($memberData);
                    $memberUuids[] = $memberUuid;
                }

                // If HOF was resolved after first pass, update head_of_family_id for all members
                if ($hofId !== null) {
                    DB::table('dbt_apy.family_members')
                        ->where('family_id', $familyUuid)
                        ->where('lgd_district_code', $districtCode)
                        ->update(['head_of_family_id' => $hofId]);
                }

                return [
                    'family_id' => $familyUuid,
                    'tmp_application_id' => $tmpAppUuid,
                    'total_members' => $totalMembers,
                    'annapurna_beneficiaries' => $annapurnaBeneficiaries,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Family and members registered successfully in dbt_apy tables!',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch seed realistic Indian origin families.
     * Supports presets: 10, 20, 25, 50, 75, 100, 150, 200, 250, 450, 500 up to 1000.
     */
    public function batchSeed(Request $request): JsonResponse
    {
        @set_time_limit(300);
        $count = (int) $request->input('count', 1);
        $districtCode = $request->input('district_code') ? (int) $request->input('district_code') : null;
        $areaType = $request->input('area_type') ?: null;

        try {
            $result = IndianDataService::seedBatch($count, $districtCode, $areaType);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seeding failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Provide a single generated Indian family for UI instant pre-filling.
     */
    public function getSample(Request $request): JsonResponse
    {
        $districtCode = $request->input('district_code') ? (int) $request->input('district_code') : null;
        $areaType = $request->input('area_type') ?: null;

        $sample = IndianDataService::generateFamily($districtCode, $areaType);

        return response()->json([
            'success' => true,
            'data' => $sample,
        ]);
    }

    /**
     * Get paginated family records with their members.
     */
    public function getRecords(Request $request): JsonResponse
    {
        $query = DB::table('dbt_apy.families')
            ->orderBy('created_at', 'desc');

        if ($request->has('district_code') && $request->input('district_code') !== '') {
            $query->where('lgd_district_code', (int) $request->input('district_code'));
        }

        if ($request->has('search') && $request->input('search') !== '') {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('address', 'ILIKE', $term)
                    ->orWhereRaw('id::text ILIKE ?', [$term])
                    ->orWhereRaw('tmp_application_id::text ILIKE ?', [$term]);
            });
        }

        $families = $query->limit(50)->get();

        // Attach members
        $familyIds = $families->pluck('id')->toArray();
        $members = DB::table('dbt_apy.family_members')
            ->whereIn('family_id', $familyIds)
            ->orderBy('is_hof', 'desc')
            ->get()
            ->groupBy('family_id');

        $result = $families->map(function ($f) use ($members) {
            $f->members = $members->get($f->id, collect());

            return $f;
        });

        return response()->json([
            'success' => true,
            'count' => $result->count(),
            'data' => $result,
        ]);
    }

    /**
     * Delete a family and its members.
     */
    public function destroy(string $id, int $districtCode): JsonResponse
    {
        try {
            DB::transaction(function () use ($id, $districtCode) {
                DB::table('dbt_apy.family_members')
                    ->where('family_id', $id)
                    ->where('lgd_district_code', $districtCode)
                    ->delete();

                DB::table('dbt_apy.families')
                    ->where('id', $id)
                    ->where('lgd_district_code', $districtCode)
                    ->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete record: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get Subdistricts for a district.
     */
    public function apiSubdistricts(int $districtCode): JsonResponse
    {
        return response()->json(MasterDataService::getSubdistricts($districtCode));
    }

    /**
     * API: Get ULBs (Municipalities) for a district and subdistrict.
     */
    public function apiUlbs(int $districtCode, ?int $subDistrictCode = null): JsonResponse
    {
        return response()->json(MasterDataService::getUlbs($districtCode, $subDistrictCode));
    }

    /**
     * API: Get Wards for an urban local body.
     */
    public function apiWards(int $urbanBodyCode): JsonResponse
    {
        return response()->json(MasterDataService::getUlbWards($urbanBodyCode));
    }

    /**
     * API: Get Blocks for a district.
     */
    public function apiBlocks(int $districtCode): JsonResponse
    {
        return response()->json(MasterDataService::getBlocks($districtCode));
    }

    /**
     * API: Get Gram Panchayats for a block.
     */
    public function apiGps(int $districtCode, int $blockCode): JsonResponse
    {
        return response()->json(MasterDataService::getGps($districtCode, $blockCode));
    }

    /**
     * API: Validate Aadhaar.
     */
    public function apiValidateAadhaar(Request $request): JsonResponse
    {
        $num = $request->input('aadhaar', '');
        $valid = AadhaarService::validate($num);

        return response()->json(['valid' => $valid]);
    }
}
