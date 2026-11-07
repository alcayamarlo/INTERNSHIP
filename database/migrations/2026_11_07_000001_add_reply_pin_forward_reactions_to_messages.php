<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messenger-style message actions: reply to a message, pin it in the chat, forward it,
 * remove a received message from your own view, and react with an emoji.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // "Remove for you" on a message you received (sender_removed_at covers your own).
            $table->timestamp('receiver_removed_at')->nullable()->after('sender_removed_at');
            // Reply: the message this one answers (kept even if that one is later removed).
            $table->foreignId('reply_to_id')->nullable()->after('body')->constrained('messages')->nullOnDelete();
            // Pin: shared by both people in the chat.
            $table->timestamp('pinned_at')->nullable()->after('receiver_removed_at');
            $table->foreignId('pinned_by')->nullable()->after('pinned_at')->constrained('users')->nullOnDelete();
            // Forward: this message is a copy forwarded from another chat.
            $table->timestamp('forwarded_at')->nullable()->after('pinned_by');
        });

        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamps();
            $table->unique(['message_id', 'user_id']); // one reaction per person, like Messenger
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reply_to_id');
            $table->dropConstrainedForeignId('pinned_by');
            $table->dropColumn(['receiver_removed_at', 'pinned_at', 'forwarded_at']);
        });
    }
};
