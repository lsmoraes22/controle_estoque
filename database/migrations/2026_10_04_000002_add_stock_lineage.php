<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->dropForeign(['reception_id']);
        });
        Schema::table('stock', function (Blueprint $table) {
            $table->unsignedBigInteger('reception_id')->nullable()->change();
            $table->foreign('reception_id')->references('row')->on('reception_body');
            // InnoDB supplies the FK index. Root reception uniqueness remains unchanged.
            $table->foreignId('origin_stock_id')->nullable()->constrained('stock')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Restoring NOT NULL cannot preserve split rows. Refuse before any DDL.
        if (DB::table('stock')->whereNull('reception_id')->exists()
            || DB::table('stock')->whereNotNull('origin_stock_id')->exists()) {
            throw new LogicException('Cannot remove stock lineage while derived stocks exist.');
        }
        Schema::table('stock', function (Blueprint $table) {
            $table->dropForeign(['origin_stock_id']);
            $table->dropColumn('origin_stock_id');
            $table->dropForeign(['reception_id']);
        });
        Schema::table('stock', function (Blueprint $table) {
            $table->unsignedBigInteger('reception_id')->nullable(false)->change();
            $table->foreign('reception_id')->references('row')->on('reception_body');
        });
    }
};
