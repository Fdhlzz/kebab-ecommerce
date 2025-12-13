<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('courier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('customer_name');
            $table->string('shipping_address');
            $table->string('region_code')->nullable();
            $table->decimal('total_price', 15, 2);
            $table->decimal('shipping_cost', 15, 2);
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('paid');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
