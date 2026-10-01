<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reception_body', function (Blueprint $table) {
            // Nullable for existing receptions; a linked fiscal item must be retained.
            $table->foreignId('xml_nf_body_id')->nullable()
                ->constrained('xml_nf_body')->restrictOnDelete();
            $table->decimal('theoretical', 20, 4)->nullable()
                ->comment('theoretical quantity')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reception_body', function (Blueprint $table) {
            $table->dropConstrainedForeignId('xml_nf_body_id');
            $table->integer('theoretical')->nullable()->comment('theoretical quantity')->change();
        });
    }
};
