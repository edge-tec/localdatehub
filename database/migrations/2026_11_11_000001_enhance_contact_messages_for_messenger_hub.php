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
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('message_encrypted');
            $table->string('attachment_type')->nullable()->after('attachment_path'); // image, document, audio, video
            $table->string('attachment_name')->nullable()->after('attachment_type');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_name');
            $table->boolean('is_pinned')->default(false)->after('is_replied');
            $table->boolean('is_starred')->default(false)->after('is_pinned');
            $table->boolean('is_archived')->default(false)->after('is_starred');
            $table->json('reactions')->nullable()->after('is_archived');
            $table->foreignId('reply_to_id')->nullable()->after('inquiry_type')->constrained('contact_messages')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_id']);
            $table->dropColumn([
                'attachment_path',
                'attachment_type',
                'attachment_name',
                'attachment_size',
                'is_pinned',
                'is_starred',
                'is_archived',
                'reactions',
                'reply_to_id',
            ]);
        });
    }
};
