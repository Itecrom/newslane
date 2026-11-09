<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateNewsAdmin extends Command
{
    protected $signature = 'news:admin:create {name} {email}';

    protected $description = 'Create a newsroom administrator using an interactively entered password';

    public function handle(): int
    {
        $name = $this->argument('name');
        $email = strtolower($this->argument('email'));

        if (User::where('email', $email)->exists()) {
            $this->components->error('An account with that email already exists.');

            return self::FAILURE;
        }

        $password = $this->secret('Administrator password (12+ characters)');
        $confirmation = $this->secret('Confirm password');
        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email:rfc', 'max:254', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $this->components->info("Administrator account created for {$email}.");

        return self::SUCCESS;
    }
}
