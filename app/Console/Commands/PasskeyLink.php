<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\User;

class PasskeyLink extends Command {

    protected $signature = 'user:passkey-link {email : The email address or identifier of the user}';
    protected $description = 'Print a one-time link that lets a user register a new passkey';

    const MINUTES = 30;

    public function handle() {
        if(env('AUTH_METHOD') != 'session') {
            $this->error('Passkey links are only used when AUTH_METHOD=session');
            return 1;
        }

        $email = $this->argument('email');
        $user = User::find_from_email($email) ?: User::where('identifier', $email)->first();

        if(!$user) {
            $this->error('No user found for '.$email);
            return 1;
        }

        // The nonce is part of the signed URL; the web request marks it as used
        $nonce = Str::random(40);

        $url = URL::temporarySignedRoute('passkey-link', now()->addMinutes(self::MINUTES), [
            'user' => $user->id,
            'nonce' => $nonce,
        ]);

        $this->info('Open this link within '.self::MINUTES.' minutes to register a passkey for '.($user->name ?: $user->email).'. It can only be used once.');
        $this->line($url);
        return 0;
    }

}
