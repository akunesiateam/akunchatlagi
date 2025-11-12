<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // varchar(255)
                $table->string('code', 3); // varchar(3)
                $table->string('symbol', 10)->default('$'); // default '$' only for symbol
                $table->string('format')->nullable(); // varchar(255), nullable
                $table->decimal('exchange_rate', 10, 6)->default(1.000000)->nullable(); // exact precision
                $table->boolean('is_default')->default(false); // tinyint(1) default 0
                $table->timestamps(); // created_at, updated_at (nullable by default)
            });
        }
    }

    public function down() {}
};
