<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Hanafalah\ModuleRegional\Models\Regional\{
    District,
    Province
};

return new class extends Migration
{
    use Hanafalah\LaravelSupport\Concerns\NowYouSeeMe;

    private $__table;

    public function __construct()
    {
        $this->__table = app(config('database.models.District', District::class));
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
                $province = app(config('database.models.Province', Province::class));

                $table->id();
                $table->string('code', 100)->nullable(true);
                $table->string('name', 100)->nullable(false);
                $table->char('type', 2)->nullable();
                $table->foreignIdFor($province::class)->nullable()->index()
                    ->constrained()->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('latitude', 50)->nullable();
                $table->string('longitude', 50)->nullable();
                $table->json('props')->nullable();
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
