<?php

namespace Database\Seeders;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RerunInvoiceCouponMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'coupon_id')) {
                // Add column without foreign key constraint first
                $table->unsignedBigInteger('coupon_id')->nullable()->after('currency_id');
            }

            if (! Schema::hasColumn('invoices', 'coupon_discount')) {
                $table->decimal('coupon_discount', 10, 2)->default(0)->after('coupon_id');
            }

            if (! Schema::hasColumn('invoices', 'coupon_code')) {
                $table->string('coupon_code')->nullable()->after('coupon_discount');
            }

            if (! Schema::hasColumn('invoices', 'coupon_snapshot')) {
                $table->json('coupon_snapshot')->nullable()->after('coupon_code')->comment('Snapshot of coupon data when applied');
            }
        });

        $fk = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_NAME', 'invoices')
            ->where('COLUMN_NAME', 'coupon_id')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($fk) {
            Schema::table('invoices', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk);
            });
        }

        // Add foreign key constraint if coupons table exists and coupon_id column was just created
        if (Schema::hasTable('coupons') && Schema::hasColumn('invoices', 'coupon_id')) {

            Schema::table('invoices', function (Blueprint $table) {

                $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
            });

        }

    }
}
