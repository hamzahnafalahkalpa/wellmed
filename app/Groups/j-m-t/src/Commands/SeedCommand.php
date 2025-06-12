<?php

namespace Groups\JMT\Commands;

use Illuminate\Support\Facades\Artisan;

class SeedCommand extends EnvironmentCommand{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'j-m-t:seed {class? : Class name of the seeder}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command ini digunakan untuk seeding';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $class = $this->argument('class') ?? "DatabaseSeeder";
        Artisan::call('db:seed',[
            '--class' => "Groups\JMT\\Database\Seeders\\$class"
        ]);   
    }
}