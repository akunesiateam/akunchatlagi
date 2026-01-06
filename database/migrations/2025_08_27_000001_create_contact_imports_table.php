<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('contact_imports')) {
            Schema::create('contact_imports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('file_path');
                $table->integer('total_records')->default(0);
                $table->integer('processed_records')->default(0);
                $table->integer('valid_records')->default(0);
                $table->integer('invalid_records')->default(0);
                $table->integer('skipped_records')->default(0);
                $table->string('status');
                $table->json('error_messages')->nullable();
                $table->timestamps();

                $table->index('tenant_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('contact_imports');
    }
};
