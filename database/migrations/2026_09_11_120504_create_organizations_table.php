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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();

            // Владелец карточки: связь с users (one-to-many).
            // cascadeOnDelete — если удалить юзера, его организации тоже удалятся.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Ссылка на карточку в Яндекс.Картах.
            $table->string('yandex_url', 1000);

            // Данные, которые заполняются ПОСЛЕ парсинга — поэтому nullable.
            $table->string('name')->nullable();               // название организации
            $table->decimal('rating', 3, 2)->nullable();      // средний рейтинг, напр. 4.75
            $table->unsignedInteger('ratings_count')->nullable(); // сколько ОЦЕНОК
            $table->unsignedInteger('reviews_count')->nullable(); // сколько ОТЗЫВОВ (отдельно!)

            // Статус парсинга — пригодится для фоновой обработки (очереди) и обработки ошибок.
            $table->string('status')->default('pending');     // pending | parsing | done | failed
            $table->text('last_error')->nullable();           // текст последней ошибки
            $table->timestamp('parsed_at')->nullable();       // когда последний раз успешно спарсили

            $table->timestamps();

            // один юзер не может добавить одну ссылку дважды, а разные юзеры — могут,
            // поэтому уникальность по паре (user_id, yandex_url)
            $table->unique(['user_id', 'yandex_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
