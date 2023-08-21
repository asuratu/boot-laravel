<?php

namespace ZhuiTech\BootLaravel\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\Client;

class PassportInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zhuitech:passport
                            {--force : Overwrite keys they already exist}
                            {--length=4096 : The length of the private key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '安装Laravel Passport';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->call('passport:keys', ['--force' => $this->option('force'), '--length' => $this->option('length')]);

        $name = config('app.name') . ' Personal Access Client';
        if (empty(Client::whereName($name)->first())) {
            $this->call('passport:client', ['--personal' => true, '--name' => $name]);
        }

        $name = config('app.name') . ' Password Grant Client';
        if (empty(Client::whereName($name)->first())) {
            $this->call('passport:client', ['--password' => true, '--name' => config('app.name') . ' Password Grant Client']);
        }
    }
}
