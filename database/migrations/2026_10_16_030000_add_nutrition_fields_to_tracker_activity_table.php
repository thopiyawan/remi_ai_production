<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_activity', function (Blueprint $table) {
            $table->decimal('carbohydrate', 8, 2)->nullable()->after('calorie');
            $table->decimal('protein', 8, 2)->nullable()->after('carbohydrate');
            $table->decimal('fat', 8, 2)->nullable()->after('protein');
            $table->decimal('fiber', 8, 2)->nullable()->after('fat');
            $table->string('image_path')->nullable()->after('fiber');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_activity', function (Blueprint $table) {
            $table->dropColumn(['carbohydrate', 'protein', 'fat', 'fiber', 'image_path']);
        });
    }
};
