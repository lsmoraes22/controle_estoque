<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->decimal('quantity', 20, 4)->change();
            $table->unique('reception_id');
        });

        Schema::table('journals', function (Blueprint $table) {
            // Historical movements did not record quantity.
            $table->decimal('quantity', 20, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('stock', function (Blueprint $table) {
            $table->dropUnique(['reception_id']);
            $table->integer('quantity')->change();
        });
    }
};
