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

            // Родитель отзыва — организация (one-to-many). Вот тот самый FK,
            // про который ты сказал: "говорим отзыву, что его владелец — organization_id".
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // ID отзыва СО СТОРОНЫ ЯНДЕКСА. Нужен для идемпотентности:
            // при повторном парсинге по нему находим существующий отзыв и обновляем,
            // а не создаём дубль.
            $table->string('external_id')->nullable();

            $table->string('author')->nullable();             // автор отзыва
            $table->unsignedTinyInteger('rating')->nullable(); // оценка 1..5
            $table->longText('text')->nullable();             // текст отзыва
            $table->timestamp('review_date')->nullable();     // дата отзыва (со стороны Яндекса)

            $table->timestamps();

            // Один и тот же отзыв Яндекса не может дважды лежать у одной организации.
            // Это гарантия БД против дублей (пункт 5 — идемпотентность).
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
