<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * In the Annapurna architecture, tables (families, family_members, accept_reject_info)
     * use composite primary keys including lgd_district_code specifically to enable
     * partitioning by district or zone.
     *
     * This migration installs partition helper functions and provides support for
     * managing district-level partitions across PostgreSQL tables.
     */
    public function up(): void
    {
        // ── Helper Function: Create District Partition ────────────────────────
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION dbt_apy.create_district_partition(
    parent_table text,
    partition_name text,
    district_code integer
)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
    EXECUTE format(
        'CREATE TABLE IF NOT EXISTS %I PARTITION OF %s FOR VALUES IN (%L)',
        partition_name,
        parent_table,
        district_code
    );
END;
$$;
SQL
        );

        // ── Helper Function: Create Zone Partition (List of Districts) ─────────
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION dbt_apy.create_zone_partition(
    parent_table text,
    partition_name text,
    district_codes integer[]
)
RETURNS void
LANGUAGE plpgsql
AS $$
DECLARE
    dist_list text;
BEGIN
    dist_list := array_to_string(district_codes, ', ');
    EXECUTE format(
        'CREATE TABLE IF NOT EXISTS %I PARTITION OF %s FOR VALUES IN (%s)',
        partition_name,
        parent_table,
        dist_list
    );
END;
$$;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS dbt_apy.create_zone_partition(text, text, integer[]) CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS dbt_apy.create_district_partition(text, text, integer) CASCADE');
    }
};
