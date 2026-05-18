<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tinyfinder_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('type', ['image', 'file'])->default('image');
            $table->string('name');
            $table->string('basename')->unique();
            $table->string('extension', 10);
            $table->string('mime_type', 100);

            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->boolean('has_thumbnails')->default(false);
            $table->boolean('is_private')->default(false);

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('type');
            $table->index('extension');
            $table->index('user_id');
            $table->index('is_private');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tinyfinder_files');
    }
};
