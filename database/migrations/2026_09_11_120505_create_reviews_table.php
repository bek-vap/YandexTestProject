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

            // у отзыва один владелец — организация (organization_id)
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // id отзыва у Яндекса. по нему при повторном парсинге находим
            // этот же отзыв и обновляем, а не создаём второй раз
            $table->string('external_id')->nullable();

            $table->string('author')->nullable();             // автор отзыва
            $table->unsignedTinyInteger('rating')->nullable(); // оценка 1..5
            $table->longText('text')->nullable();             // текст отзыва
            $table->timestamp('review_date')->nullable();     // дата отзыва (со стороны Яндекса)

            $table->timestamps();

            // один и тот же отзыв не может дважды лежать у одной организации — без дублей
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
