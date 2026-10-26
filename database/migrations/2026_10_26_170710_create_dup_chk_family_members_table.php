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
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS dup_chk.family_members (
    id uuid NOT NULL,
    member_name text,
    aadhaar_no text,
    mobile_no text,
    bank_account_no text,
    epic_no text,
    digital_ration_card_no text,
    CONSTRAINT family_members_pkey PRIMARY KEY (id)
);
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS dup_chk.family_members CASCADE');
    }
};
