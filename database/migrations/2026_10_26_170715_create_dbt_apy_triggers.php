<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_sync_dup_chk_family_members ON dbt_apy.family_members');

        DB::statement(<<<'SQL'
CREATE TRIGGER trg_sync_dup_chk_family_members
    AFTER INSERT OR UPDATE ON dbt_apy.family_members
    FOR EACH ROW
    EXECUTE FUNCTION dbt_apy.sync_dup_chk_family_members();
SQL
        );

        DB::statement('DROP TRIGGER IF EXISTS trg_sync_family_members_to_consolidation ON dbt_apy.family_members');

        DB::statement(<<<'SQL'
CREATE TRIGGER trg_sync_family_members_to_consolidation
    AFTER INSERT OR DELETE OR UPDATE ON dbt_apy.family_members
    FOR EACH ROW
    EXECUTE FUNCTION dbt_apy.sync_family_members_to_consolidation();
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_sync_dup_chk_family_members ON dbt_apy.family_members');
        DB::statement('DROP TRIGGER IF EXISTS trg_sync_family_members_to_consolidation ON dbt_apy.family_members');
    }
};
