<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real (recoverable) delete for notifications.
 *
 * Before this, "Delete" only archived the notification, so nothing could ever be removed.
 * deleted_at moves a notification to Trash; it can be restored or permanently deleted there.
 *
 * read_at / archived_at already exist and are the source of truth; is_read and is_archived are
 * computed from them on the model instead of being stored twice (two copies can disagree).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->softDeletes()->after('archived_at');
            // Every inbox/archive/trash/unread-count query filters by these, newest first.
            $table->index(['user_id', 'deleted_at', 'archived_at', 'read_at'], 'notifications_user_state_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_user_state_index');
            $table->dropSoftDeletes();
        });
    }
};
