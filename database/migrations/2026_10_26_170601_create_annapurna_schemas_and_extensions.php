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
        DB::statement('CREATE SCHEMA IF NOT EXISTS consolidation');
        DB::statement('CREATE SCHEMA IF NOT EXISTS dbt_apy');
        DB::statement('CREATE SCHEMA IF NOT EXISTS dup_chk');

        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS consolidation CASCADE');
        DB::statement('DROP SCHEMA IF EXISTS dbt_apy CASCADE');
        DB::statement('DROP SCHEMA IF EXISTS dup_chk CASCADE');
    }
};
