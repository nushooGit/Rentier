<?php

namespace App\Console\Commands;

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class E2eBootstrapCommand extends Command
{
    protected $signature = 'e2e:bootstrap';

    protected $description = 'Reset the guarded E2E SQLite database and seed the verified E2E user.';

    public function handle(CreateTeam $createTeam): int
    {
        $databasePath = $this->guardedDatabasePath();
        $this->guardLocalAppUrl();

        File::ensureDirectoryExists(dirname($databasePath));

        if (! File::exists($databasePath)) {
            File::put($databasePath, '');
        }

        Artisan::call('migrate:fresh', [
            '--force' => true,
        ]);

        $email = env('E2E_EMAIL', 'e2e@rentier.test');
        $password = env('E2E_PASSWORD', 'password');

        $user = User::query()->create([
            'name' => 'E2E Smoke User',
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $team = $createTeam->handle($user, 'E2E Smoke Workspace', isPersonal: true);
        $user->forceFill(['current_team_id' => $team->id])->save();

        $this->components->info("E2E database reset at {$databasePath}.");
        $this->components->info("Seeded verified E2E user {$email} in workspace {$team->slug}.");

        return self::SUCCESS;
    }

    private function guardedDatabasePath(): string
    {
        if (app()->environment() !== 'e2e') {
            $this->fail('Refusing E2E reset because APP_ENV is not exactly e2e.');
        }

        if (config('database.default') !== 'sqlite') {
            $this->fail('Refusing E2E reset because DB_CONNECTION is not sqlite.');
        }

        $configuredPath = (string) config('database.connections.sqlite.database');

        if ($configuredPath === '' || $configuredPath === ':memory:') {
            $this->fail('Refusing E2E reset because DB_DATABASE is not a dedicated file path.');
        }

        $path = $this->absolutePath($configuredPath);
        $normalized = str_replace('\\', '/', strtolower($path));

        if (! str_contains($normalized, 'e2e')) {
            $this->fail('Refusing E2E reset because DB_DATABASE does not clearly identify an E2E database.');
        }

        if (basename($normalized) === 'database.sqlite') {
            $this->fail('Refusing E2E reset because DB_DATABASE points at the normal local SQLite database.');
        }

        return $path;
    }

    private function guardLocalAppUrl(): void
    {
        $url = (string) config('app.url');
        $host = parse_url($url, PHP_URL_HOST);

        if (! in_array($host, ['127.0.0.1', 'localhost'], true)) {
            $this->fail('Refusing E2E reset because APP_URL is not localhost or 127.0.0.1.');
        }

        if (str_contains(strtolower($url), 'rentier.ro')) {
            $this->fail('Refusing E2E reset because APP_URL targets rentier.ro.');
        }
    }

    private function absolutePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}
