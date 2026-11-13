<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('RecordOfPregnancy', function (Blueprint $table) {
            $table->decimal('target_weight_min', 5, 2)->nullable()->after('preg_weight');
            $table->decimal('target_weight_max', 5, 2)->nullable()->after('target_weight_min');
        });
    }

    public function down(): void
    {
        Schema::table('RecordOfPregnancy', function (Blueprint $table) {
            $table->dropColumn(['target_weight_min', 'target_weight_max']);
        });
    }
};
