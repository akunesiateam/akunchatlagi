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
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->after('currency_id')->constrained()->onDelete('set null');
            }

            if (! Schema::hasColumn('invoices', 'coupon_discount')) {
                $table->decimal('coupon_discount', 10, 2)->default(0)->after('coupon_id');
            }

            if (! Schema::hasColumn('invoices', 'coupon_code')) {
                $table->string('coupon_code')->nullable()->after('coupon_discount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'coupon_id')) {
                $table->dropForeign(['coupon_id']);
            }

            $cols = [];
            if (Schema::hasColumn('invoices', 'coupon_id')) {
                $cols[] = 'coupon_id';
            }
            if (Schema::hasColumn('invoices', 'coupon_discount')) {
                $cols[] = 'coupon_discount';
            }
            if (Schema::hasColumn('invoices', 'coupon_code')) {
                $cols[] = 'coupon_code';
            }

            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
