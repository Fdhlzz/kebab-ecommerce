<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label'); // e.g., "Rumah", "Kantor"
            $table->string('recipient_name');
            $table->string('phone_number');
            $table->string('district_id'); // String (from your indonesia_regions)
            $table->text('full_address');
            $table->boolean('is_primary')->default(false); // To mark the active address
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
