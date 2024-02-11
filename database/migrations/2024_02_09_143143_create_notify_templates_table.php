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
        Schema::create('notify_templates', function (Blueprint $table) {
            $table->id();
            $table->string('session')->nullable();
            $table->string('receiver');
            $table->string('slug');
            $table->string('title');
            $table->text('mail_msg')->nullable();
            $table->text('notification_msg')->nullable();
            $table->string('variables');
            $table->boolean('is_mail');
            $table->boolean('is_push');
            $table->boolean('is_whatsapp')->default(0);
            $table->boolean('is_sms')->default(0);
            $table->boolean('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notify_templates');
    }
};
