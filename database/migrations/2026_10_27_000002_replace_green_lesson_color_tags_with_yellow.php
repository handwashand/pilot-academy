<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lessons')
            ->where('color_tag', 'green')
            ->update(['color_tag' => 'yellow']);
    }

    public function down(): void
    {
        DB::table('lessons')
            ->where('color_tag', 'yellow')
            ->update(['color_tag' => 'green']);
    }
};
