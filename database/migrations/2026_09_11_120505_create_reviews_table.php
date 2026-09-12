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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // id отзыва у Яндекса — по нему при повторном парсинге обновляем, а не дублируем
            $table->string('external_id')->nullable();

            $table->string('author')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->longText('text')->nullable();
            $table->timestamp('review_date')->nullable();

            $table->timestamps();

            // защита от дублей отзывов у одной организации
            $table->unique(['organization_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
