<?php

namespace Tests\Feature;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionConfigurationTest extends TestCase
{
    /**
     * The regression that produced "Please provide a valid cache path." was a
     * compiled-view path that resolved to false when the directory was absent.
     */
    public function test_compiled_view_path_is_absolute_existing_and_writable(): void
    {
        $compiled = config('view.compiled');

        $this->assertIsString($compiled);
        $this->assertNotSame('', trim($compiled));
        $this->assertNotFalse(realpath($compiled));
        $this->assertDirectoryExists($compiled);
        $this->assertTrue(is_writable($compiled));
    }

    public function test_view_paths_point_at_the_application_views_directory(): void
    {
        $this->assertIsArray(config('view.paths'));
        $this->assertContains(resource_path('views'), config('view.paths'));
    }

    public function test_view_config_falls_back_to_storage_path_when_override_is_empty(): void
    {
        $this->withEnvironmentVariable('VIEW_COMPILED_PATH', '', function (): void {
            $config = require base_path('config/view.php');

            $this->assertSame(storage_path('framework/views'), $config['compiled']);
        });
    }

    public function test_view_config_uses_a_valid_override(): void
    {
        $custom = storage_path('framework/views/custom-override');

        $this->withEnvironmentVariable('VIEW_COMPILED_PATH', $custom, function () use ($custom): void {
            $config = require base_path('config/view.php');

            $this->assertSame($custom, $config['compiled']);
        });
    }

    public function test_blade_template_renders_through_the_compiler(): void
    {
        $html = view('errors.500')->render();

        $this->assertNotSame('', trim($html));
    }

    public function test_default_filesystem_disk_is_configured(): void
    {
        $this->assertSame('local', config('filesystems.default'));
        $this->assertInstanceOf(Filesystem::class, Storage::disk());
    }

    public function test_required_storage_directories_exist_and_are_writable(): void
    {
        $directories = [
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $directory) {
            $this->assertDirectoryExists($directory);
            $this->assertTrue(is_writable($directory), "Directory is not writable: {$directory}");
        }
    }

    public function test_application_debug_mode_is_disabled_and_key_is_present(): void
    {
        $this->assertFalse((bool) config('app.debug'));
        $this->assertNotEmpty(config('app.key'));
    }

    /**
     * MySQL rejects the campaigns migration if email_accounts does not exist
     * yet, because campaigns.email_account_id is a foreign key. SQLite accepts
     * that ordering, so the test suite alone does not catch this regression.
     */
    public function test_email_accounts_migration_runs_before_campaigns_migration(): void
    {
        $migrations = glob(database_path('migrations/*.php')) ?: [];
        sort($migrations);

        $emailAccounts = null;
        $campaigns = null;

        foreach ($migrations as $index => $path) {
            $filename = basename($path);

            if (str_contains($filename, 'create_email_accounts_table')) {
                $emailAccounts = $index;
            }

            if (str_contains($filename, 'create_campaigns_table')) {
                $campaigns = $index;
            }
        }

        $this->assertNotNull($emailAccounts, 'The email_accounts migration is missing.');
        $this->assertNotNull($campaigns, 'The campaigns migration is missing.');
        $this->assertLessThan($campaigns, $emailAccounts);
    }

    /**
     * Shared hosting has no worker daemon. The scheduler must register the
     * in-process drain; a plain "queue:work" event crashes this host's CLI.
     */
    public function test_scheduler_registers_the_in_process_queue_drain(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('Drain database queue')
            ->assertSuccessful();

        $this->assertFileExists(base_path('queue-drain.php'));
    }

    /**
     * Run a callback with a temporary environment value and always restore it,
     * so the view config can be required without leaking state into other tests.
     */
    private function withEnvironmentVariable(string $key, string $value, callable $callback): void
    {
        $originalEnv = $_ENV[$key] ?? null;
        $originalServer = $_SERVER[$key] ?? null;
        $originalGetenv = getenv($key);

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");

        try {
            $callback();
        } finally {
            if ($originalEnv === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $originalEnv;
            }

            if ($originalServer === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $originalServer;
            }

            if ($originalGetenv === false) {
                putenv($key);
            } else {
                putenv("{$key}={$originalGetenv}");
            }
        }
    }
}
