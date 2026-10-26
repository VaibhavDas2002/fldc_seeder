<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Family Master Model representing dbt_apy.families table.
 */
class Family extends Model
{
    use HasFactory;

    protected $table = 'dbt_apy.families';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'total_family_members' => 'integer',
        'lifting_monthly_ration' => 'boolean',
        'has_digital_ration_card' => 'boolean',
        'no_of_illiterate_adults' => 'integer',
        'no_of_literate_adults' => 'integer',
        'total_annual_family_income' => 'integer',
        'lgd_district_code' => 'integer',
        'lgd_block_mc_code' => 'integer',
        'lgd_gp_ward_code' => 'integer',
        'ulb' => 'integer',
        'is_agreed' => 'boolean',
        'under_enquiry' => 'boolean',
        'is_eligible' => 'boolean',
        'transfer_out' => 'boolean',
        'is_claim' => 'boolean',
        'is_grievance' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    /**
     * Relationship to family members.
     *
     * @return HasMany<FamilyMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class, 'family_id', 'id');
    }
}
