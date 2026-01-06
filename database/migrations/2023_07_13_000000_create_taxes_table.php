<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('taxes')) {
            Schema::create('taxes', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // varchar(255)
                $table->decimal('rate', 8, 2); // Decimal with 2 decimal places
                $table->string('description')->nullable(); // varchar(255), nullable
                $table->timestamps(); // created_at, updated_at (nullable by default)
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('taxes');
    }
};
