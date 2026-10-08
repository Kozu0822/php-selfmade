<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('symptoms')
            ->where('name', '音が出ない')
            ->update([
                'name' => 'スピーカーの不具合',
                'description' => '本体スピーカーから音が出ない、音が割れる、雑音が出る状態です。',
            ]);
    }

    public function down(): void
    {
        DB::table('symptoms')
            ->where('name', 'スピーカーの不具合')
            ->update([
                'name' => '音が出ない',
                'description' => 'スピーカーから音が出ない、音が割れる状態です。',
            ]);
    }
};
