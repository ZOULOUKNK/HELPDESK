<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['open','in_progress','waiting_client','resolved','closed'])->default('open');
            $table->enum('priority', ['low','medium','high','critical'])->default('medium');
            $table->enum('category', ['bug','feature_request','access','hardware','software','network','other'])->default('other');
            $table->string('department')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->integer('satisfaction_rating')->nullable();
            $table->text('satisfaction_comment')->nullable();
            $table->timestamps();

            $table->index(['status','priority']);
            $table->index(['user_id','status']);
            $table->index(['assigned_to','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};