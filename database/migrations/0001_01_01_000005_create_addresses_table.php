<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Hanafalah\ModuleRegional\Models\Regional\{
    Address,
    District,
    Province,
    Subdistrict,
    Village
};
use Hanafalah\ModuleRegional\Enums\Address\Flag;

return new class extends Migration
{
    use Hanafalah\LaravelSupport\Concerns\NowYouSeeMe;

    private $__table;

    public function __construct()
    {
        $this->__table = app(config('database.models.Address', Address::class));
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $table_name = $this->__table->getTable();
        if (!$this->isTableExists()) {
            Schema::create($table_name, function (Blueprint $table) {
                $province    = app(config('database.models.Province', Province::class));
                $district    = app(config('database.models.District', District::class));
                $subdistrict = app(config('database.models.Subdistrict', Subdistrict::class));
                $village     = app(config('database.models.Village', Village::class));

                $table->ulid('id')->primary();
                $table->text('name')->nullable(false);
                $table->string('model_type', 50)->nullable(false);
                $table->string('model_id', 36)->nullable(false);
                $table->enum('flag', [
                    Flag::KTP->value,
                    Flag::RESIDENCE->value,
                    Flag::OTHER->value
                ])->nullable(false);

                $table->foreignIdFor($province::class)->nullable(true)->index()
                    ->cascadeOnUpdate()->cascadeOnDelete();

                $table->foreignIdFor($district::class)->nullable(true)->index()
                    ->cascadeOnUpdate()->cascadeOnDelete();

                $table->foreignIdFor($subdistrict::class)->nullable(true)->index()
                    ->cascadeOnUpdate()->cascadeOnDelete();

                $table->foreignIdFor($village::class)->nullable(true)->index()
                    ->cascadeOnUpdate()->cascadeOnDelete();

                $table->json('props')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['model_type', 'model_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->__table->getTable());
    }
};
