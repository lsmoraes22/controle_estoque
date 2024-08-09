<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier',15)->comment('supplier name')->nullable(false);
            $table->string('address')->comment('supplier address')->nullable(false);
            $table->string('phone1',15)->comment('supplier phone 1')->nullable(false);
            $table->string('phone2',15)->comment('supplier phone 2')->nullable(true);
            $table->string('phone3',15)->comment('supplier phone 3')->nullable(true);
            $table->string('email')->comment('supplier documents')->nullable(false)->unique();
            $table->string('cnpj',18)->comment('cnpj')->nullable(false)->unique();
            $table->string('ie',15)->comment('inscrição estadual')->nullable(true);
            $table->boolean('enabled')->default(true);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
