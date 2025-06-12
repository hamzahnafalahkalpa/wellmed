<?php

namespace Groups\JMT\Commands;

class InstallMakeCommand extends EnvironmentCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'j-m-t:install';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used for initial installation of this module';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $provider = 'Groups\JMT\JMTServiceProvider';

        $this->comment('Installing Module...');
        $this->callSilent('vendor:publish', [
            '--provider' => $provider,
            '--tag'      => 'config'
        ]);
        $this->info('✔️  Created config/j-m-t.php');

        $this->callSilent('vendor:publish', [
            '--provider' => $provider,
            '--tag'      => 'migrations'
        ]);
        $this->info('✔️  Created migrations');

        $this->comment('groups/j-m-t installed successfully.');
    }
}
