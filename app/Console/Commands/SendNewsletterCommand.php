<?php

namespace App\Console\Commands;

use App\Notifications\NewsletterNotification;
use App\User;
use Illuminate\Console\Command;

class SendNewsletterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:newsletter {emails?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia un correo electronico';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $emails = $this->argument('emails');

        $builder = User::query()->whereNotNull('email_verified_at');

        if ($emails) {
            $builder->whereIn('email', $emails);
        }

        $count = $builder->count();


        if ($count) {
            $this->output->createProgressBar($count);

            $this->output->progressStart();

            $builder
                ->each(function (User $user) {
                    $user->notify(new NewsletterNotification());
                    $this->output->progressAdvance();
                });

            $this->output->progressFinish();

            $this->info("Se enviaron $count correos");

            return;
        }

        $this->info('No se envio nuingun correo');

        return;
    }
}
