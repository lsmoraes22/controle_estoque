<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xml_nf_body', function (Blueprint $table) {
            // Supports the full normalized DECIMAL(20,4) quantity accepted by the parser.
            $table->string('qCom', 21)->nullable()->change();
            // 12 integer digits, decimal point, and 10 fractional digits.
            $table->string('vUnCom', 23)->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_price', 22, 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_price', 20, 2)->nullable()->change();
        });

        Schema::table('xml_nf_body', function (Blueprint $table) {
            $table->string('qCom', 15)->nullable()->change();
            $table->string('vUnCom', 22)->nullable()->change();
        });
    }
};
