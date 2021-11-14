<?php

namespace App\Console\Commands;

use App\Notifications\VerificationEmailNotification;
use App\User;
use Illuminate\Console\Command;

class SendVerficationEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:verification {emails?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia correo de verificacion';

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

        $builder = User::query()
            ->whereDate('created_at', '>=', now()->subDays(7)->format('Y-m-d'))
            ->whereNull('email_verified_at');

        if ($emails) {
            $builder->whereIn('email', $emails);
        }

        $count = $builder->count();


        if ($count) {
            $this->output->createProgressBar($count);

            $this->output->progressStart();

            $builder
                ->each(function (User $user) {
                    $user->notify(new VerificationEmailNotification());
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
