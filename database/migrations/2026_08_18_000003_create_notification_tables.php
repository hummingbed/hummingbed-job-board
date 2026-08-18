<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('in_app')->default(true);
            $table->boolean('email')->default(true);
            $table->boolean('application_updates')->default(true);
            $table->boolean('job_alerts')->default(true);
            $table->boolean('interview_reminders')->default(true);
            $table->timestamps();
        });
        Schema::create('job_alert_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_alert_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->timestamp('delivered_at');
            $table->unique(['job_alert_id', 'job_listing_id']);
        });
        Schema::table('interviews', fn (Blueprint $table) => $table->timestamp('reminder_sent_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('interviews', fn (Blueprint $table) => $table->dropColumn('reminder_sent_at'));
        Schema::dropIfExists('job_alert_deliveries');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
