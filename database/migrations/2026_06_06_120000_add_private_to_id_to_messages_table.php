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
        Schema::table('messages', function (Blueprint $table) {
            // When set, the message is a private "whisper" visible only to the
            // sender (user_id) and this single recipient — nobody else, not even
            // other staff. NULL = an ordinary message.
            $table->foreignId('private_to_id')
                ->nullable()
                ->after('visibility')
                ->constrained('craftable_pro_users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['private_to_id']);
            $table->dropColumn('private_to_id');
        });
    }
};
