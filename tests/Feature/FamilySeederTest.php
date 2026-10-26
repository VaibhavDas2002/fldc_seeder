<?php

namespace Tests\Feature;

use App\Services\AadhaarService;
use App\Services\MasterDataService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FamilySeederTest extends TestCase
{
    /**
     * Test authentication-less UI route is directly accessible.
     */
    public function test_ui_is_accessible_without_authentication(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Annapurna Consolidated');
        $response->assertSee('dbt_apy.families');
        $response->assertSee('dbt_apy.family_members');
        $response->assertSee('No Auth Required');
    }

    /**
     * Test Aadhaar Verhoeff generator and validator.
     */
    public function test_aadhaar_verhoeff_validation(): void
    {
        $validAadhaar = AadhaarService::generate();
        $this->assertEquals(12, strlen($validAadhaar));
        $this->assertTrue(AadhaarService::validate($validAadhaar));

        // Invalid aadhaar
        $invalid = substr($validAadhaar, 0, 11).((int) substr($validAadhaar, -1) === 9 ? '0' : '9');
        $this->assertFalse(AadhaarService::validate($invalid));
    }

    /**
     * Test MasterDataService parses LGD hierarchy correctly.
     */
    public function test_master_data_service_lgd_hierarchy(): void
    {
        $districts = MasterDataService::getDistricts();
        $this->assertNotEmpty($districts);

        $blocks = MasterDataService::getBlocks(303);
        $this->assertNotEmpty($blocks);

        $ulbs = MasterDataService::getUlbs(303);
        $this->assertNotEmpty($ulbs);
    }

    /**
     * Test Annapurna Bhandar eligibility rule:
     * "family may contain a female member . if it is and the age is between 25-60 years then the column 'applying_for_annapurna_bhandar' be true"
     */
    public function test_annapurna_bhandar_rule_and_family_insertion(): void
    {
        $aadhaar1 = AadhaarService::generate();
        $aadhaar2 = AadhaarService::generate();

        $payload = [
            'lgd_district_code' => 315, // KMC
            'lgd_block_mc_code' => 250299,
            'lgd_gp_ward_code' => 2124,
            'area_type' => 'Urban',
            'ulb' => 250299,
            'address' => '24/1 Ballygunge Circular Road, Kolkata',
            'total_annual_family_income' => 140000,
            'has_digital_ration_card' => 1,
            'ration_card_household_id' => 'RC12345678',
            'lifting_monthly_ration' => 1,
            'is_agreed' => 1,
            'members' => [
                [
                    'member_name' => 'Aparna Mukherjee',
                    'gender' => 'Female',
                    'date_of_birth' => '1991-03-10', // Age ~35 (between 25-60) -> MUST be true
                    'relation_with_head_of_family' => 'Self',
                    'is_hof' => 1,
                    'aadhaar_no' => $aadhaar1,
                    'mobile_no' => '9830111222',
                    'social_category' => 'General',
                ],
                [
                    'member_name' => 'Subir Mukherjee',
                    'gender' => 'Male',
                    'date_of_birth' => '1987-08-25', // Male -> MUST be false
                    'relation_with_head_of_family' => 'Spouse',
                    'is_hof' => 0,
                    'aadhaar_no' => $aadhaar2,
                    'mobile_no' => '9830111223',
                    'social_category' => 'General',
                ],
            ],
        ];

        $response = $this->postJson('/api/family/store', $payload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $familyId = $response->json('data.family_id');
        $this->assertNotNull($familyId);

        // Verify in PostgreSQL table dbt_apy.families
        $familyRow = DB::table('dbt_apy.families')->where('id', $familyId)->first();
        $this->assertNotNull($familyRow);
        $this->assertEquals(2, $familyRow->total_family_members);

        // Verify in PostgreSQL table dbt_apy.family_members
        $members = DB::table('dbt_apy.family_members')->where('family_id', $familyId)->get();
        $this->assertCount(2, $members);

        $femaleMember = $members->firstWhere('gender', 'Female');
        $maleMember = $members->firstWhere('gender', 'Male');

        $this->assertNotNull($femaleMember);
        $this->assertNotNull($maleMember);

        // Female aged 35 MUST have applying_for_annapurna_bhandar = true
        $this->assertTrue((bool) $femaleMember->applying_for_annapurna_bhandar);

        // Male MUST have applying_for_annapurna_bhandar = false
        $this->assertFalse((bool) $maleMember->applying_for_annapurna_bhandar);

        // Clean up test data
        DB::table('dbt_apy.family_members')->where('family_id', $familyId)->delete();
        DB::table('dbt_apy.families')->where('id', $familyId)->delete();
    }

    /**
     * Test Batch Seeding of Indian Origin families.
     */
    public function test_batch_seeding_indian_families(): void
    {
        $response = $this->postJson('/api/family/batch-seed', [
            'count' => 2,
            'district_code' => 315,
            'area_type' => 'Urban',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'count' => 2]);

        $familyIds = collect($response->json('families'))->pluck('family_id')->toArray();
        $this->assertCount(2, $familyIds);

        // Clean up
        DB::table('dbt_apy.family_members')->whereIn('family_id', $familyIds)->delete();
        DB::table('dbt_apy.families')->whereIn('id', $familyIds)->delete();
    }

    /**
     * Test Artisan app:seed-families command.
     */
    public function test_artisan_bulk_seed_command(): void
    {
        $initialCount = DB::table('dbt_apy.families')->count();

        $this->artisan('app:seed-families', ['count' => 10])
            ->assertExitCode(0);

        $newCount = DB::table('dbt_apy.families')->count();
        $this->assertEquals($initialCount + 10, $newCount);
    }
}
