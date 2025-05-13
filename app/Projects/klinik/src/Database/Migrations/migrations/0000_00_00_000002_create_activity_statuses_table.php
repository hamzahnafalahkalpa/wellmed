<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Hanafalah\LaravelSupport\Concerns\NowYouSeeMe;
use Hanafalah\MicroTenant\Models\Activity\{
    CentralActivity,
    CentralActivityStatus
};

return new class extends Migration
{
    use NowYouSeeMe;

    private $__table;

    public function __construct()
    {
        $this->__table = app(config('database.models.CentralActivityStatus', CentralActivityStatus::class));
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table_name = $this->__table->getTableName();
        if (!$this->isTableExists()) {
            Schema::create($table_name, function (Blueprint $table) {
                $centralActivity = app(config('database.models.CentralActivity', CentralActivity::class));
                $table->ulid('id')->primary();
                $table->foreignIdFor($centralActivity::class, 'activity_id')->nullable()->index()
                    ->constrained('activities', 'id')->cascadeOnUpdate()->cascadeOnDelete();
                $table->unsignedBigInteger('status');
                $table->unsignedTinyInteger('active')->default(1);
                $table->text('message');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->__table->getTableName());
    }
};
