<?php

use Hanafalah\ModuleEncoding\Models\Encoding\Encoding;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    use Hanafalah\LaravelSupport\Concerns\NowYouSeeMe;
    private $__table;

    public function __construct()
    {
        $this->__table = app(config('database.models.Encoding', Encoding::class));
    }
    public function up(): void
    {
        $table_name = $this->__table->getTable();
        if (!$this->isTableExists()) {
            Schema::create($table_name, function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->string('name',100);
                $table->string('flag', 45)->nullable();
                $table->timestamps();
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
