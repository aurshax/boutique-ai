<?php

use App\Enums\TryOnStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('try_on_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();               // URL'da ko'rinadi — taxmin qilib bo'lmasligi uchun UUID
            $table->string('status', 16)->default(TryOnStatus::Queued->value);
            $table->foreignId('look_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fabric_id')->nullable()->constrained()->nullOnDelete();

            $table->smallInteger('height_cm');
            $table->smallInteger('waist_cm')->nullable();
            $table->string('size', 8)->nullable();        // mijoz aytgan odatdagi razmer

            $table->jsonb('params')->default('{}');       // look tarkibi, quality, o'lcham va h.k.
            $table->string('prompt_version', 32);
            $table->string('provider', 32);               // mock | openai
            $table->string('model', 64)->nullable();

            $table->string('upload_path')->nullable();    // private/uploads/...
            $table->string('result_path')->nullable();    // private/results/...
            $table->decimal('cost_usd', 8, 4)->nullable();
            $table->text('error')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            // Eslatma: OpenAI kaliti, mijoz ismi yoki telefoni bu jadvalda YO'Q — ataylab.
        });

        $statuses = "'".implode("','", TryOnStatus::values())."'";
        DB::statement("ALTER TABLE try_on_requests ADD CONSTRAINT try_on_status_check CHECK (status IN ({$statuses}))");
        DB::statement('ALTER TABLE try_on_requests ADD CONSTRAINT try_on_height_check CHECK (height_cm BETWEEN 120 AND 230)');
        DB::statement('ALTER TABLE try_on_requests ADD CONSTRAINT try_on_waist_check CHECK (waist_cm IS NULL OR waist_cm BETWEEN 50 AND 160)');
        DB::statement('ALTER TABLE try_on_requests ADD CONSTRAINT try_on_cost_check CHECK (cost_usd IS NULL OR cost_usd >= 0)');

        // Partial index: faqat kutilayotgan so'rovlar indekslanadi — kichik va tez.
        DB::statement("CREATE INDEX try_on_requests_queued_idx ON try_on_requests (created_at) WHERE status = 'queued'");
        // TTL tozalash uchun: tugagan so'rovlarni yaratilgan sanasi bo'yicha topish.
        DB::statement("CREATE INDEX try_on_requests_finished_idx ON try_on_requests (created_at) WHERE status IN ('done','failed','cancelled')");
        // GIN indeks: params ichidan qidirish, masalan params @> '{"quality":"high"}'
        DB::statement('CREATE INDEX try_on_requests_params_gin ON try_on_requests USING gin (params jsonb_path_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('try_on_requests');
    }
};
