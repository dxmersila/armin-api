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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('amount');

            $table->string('gateway_transaction_id')
                ->nullable()
                ->after('gateway');

            $table->timestamp('paid_at')
                ->nullable()
                ->after('gateway_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'gateway',
                'gateway_transaction_id',
                'paid_at',
            ]);
        });
    }
};
