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
            if (!Schema::hasColumn('contact_messages', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('message_encrypted');
            }
            if (!Schema::hasColumn('contact_messages', 'attachment_type')) {
                $table->string('attachment_type')->nullable()->after('attachment_path'); // image, file
            }
            if (!Schema::hasColumn('contact_messages', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_type');
            }
            if (!Schema::hasColumn('contact_messages', 'attachment_size')) {
                $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_name');
            }
            if (!Schema::hasColumn('contact_messages', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('is_replied');
            }
            if (!Schema::hasColumn('contact_messages', 'is_starred')) {
                $table->boolean('is_starred')->default(false)->after('is_pinned');
            }
            if (!Schema::hasColumn('contact_messages', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_starred');
            }
            if (!Schema::hasColumn('contact_messages', 'reactions')) {
                $table->json('reactions')->nullable()->after('is_archived');
            }
            if (!Schema::hasColumn('contact_messages', 'reply_to_id')) {
                $table->foreignId('reply_to_id')->nullable()->after('inquiry_type')->constrained('contact_messages')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            if (Schema::hasColumn('contact_messages', 'reply_to_id')) {
                $table->dropForeign(['reply_to_id']);
            }
            $columns = [
                'attachment_path',
                'attachment_type',
                'attachment_name',
                'attachment_size',
                'is_pinned',
                'is_starred',
                'is_archived',
                'reactions',
                'reply_to_id',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('contact_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
