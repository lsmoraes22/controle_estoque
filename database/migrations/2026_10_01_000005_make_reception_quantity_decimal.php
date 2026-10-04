<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reception_body', function (Blueprint $table) {
            $table->decimal('quantity', 20, 4)->nullable()->comment('physical quantity; unknown before conference')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reception_body', function (Blueprint $table) {
            $table->integer('quantity')->nullable()->comment('physical quantity; unknown before conference')->change();
        });
    }
};
