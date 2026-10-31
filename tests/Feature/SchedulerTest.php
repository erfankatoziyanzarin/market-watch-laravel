<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = sys_get_temp_dir().'/market sync '.bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory, 0700);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory.'/*') as $file) {
            unlink($file);
        }
        rmdir($this->temporaryDirectory);

        parent::tearDown();
    }

    public function test_scheduler_runs_background_sync_every_ten_seconds_with_hard_process_deadline(): void
    {
        $events = app(Schedule::class)->events();
        $event = collect($events)->first(fn ($event): bool => $event->description === 'market-sync');

        $this->assertNotNull($event);
        $this->assertSame(10, $event->repeatSeconds);
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->runInBackground);
        $this->assertStringContainsString(base_path('bin/sync-market'), $event->command);
        $this->assertStringContainsString(PHP_BINARY, $event->command);
    }

    #[DataProvider('commandExitCodes')]
    public function test_scheduler_wrapper_preserves_arguments_output_and_exit_code(int $exitCode): void
    {
        $php = $this->fakePhp('printf "%s\\n" "$PWD" "$@"; printf "command diagnostic\\n" >&2; exit '.$exitCode);
        $process = new Process([base_path('bin/sync-market'), $php], $this->temporaryDirectory);

        $this->assertSame($exitCode, $process->run());
        $this->assertSame(base_path()."\n".base_path('artisan')."\ninstruments:sync\n", $process->getOutput());
        $this->assertSame("command diagnostic\n", $process->getErrorOutput());
    }

    public static function commandExitCodes(): array
    {
        return ['success' => [0], 'command failure' => [7]];
    }

    public function test_wrapper_terminates_slow_commands_and_kills_commands_ignoring_term(): void
    {
        $normalPhp = $this->fakePhp('exec /bin/sleep 90', 'normal php');
        $stubbornPhp = $this->fakePhp("trap '' TERM\nexec /bin/sleep 90", 'stubborn php');
        $normal = new Process([base_path('bin/sync-market'), $normalPhp], timeout: 55);
        $stubborn = new Process([base_path('bin/sync-market'), $stubbornPhp], timeout: 55);
        $started = microtime(true);

        try {
            $normal->start();
            $stubborn->start();

            $this->assertSame(124, $normal->wait());
            $this->assertGreaterThanOrEqual(39, microtime(true) - $started);

            try {
                $stubborn->wait();
            } catch (ProcessSignaledException) {
                $this->assertSame(9, $stubborn->getTermSignal());
            }

            $this->assertSame(137, $stubborn->getExitCode());
            $this->assertGreaterThanOrEqual(44, microtime(true) - $started);
        } finally {
            $normal->stop(0);
            $stubborn->stop(0);
        }
    }

    private function fakePhp(string $body, string $name = 'fake php'): string
    {
        $path = $this->temporaryDirectory.'/'.$name;
        file_put_contents($path, "#!/usr/bin/env bash\nset -euo pipefail\n".$body."\n");
        chmod($path, 0700);

        return $path;
    }

    public function test_scheduler_logs_a_process_timeout_after_background_exit(): void
    {
        Log::spy();
        $event = collect(app(Schedule::class)->events())->first(fn ($event): bool => $event->description === 'market-sync');

        $event->finish($this->app, 124);

        Log::shouldHaveReceived('error')->with('market_sync_process_failed', \Mockery::on(fn (array $context): bool => $context['exit_code'] === 124 && $context['reason'] === 'process_deadline_exceeded'))->once();
    }

    public function test_scheduler_distinguishes_an_ordinary_command_failure(): void
    {
        Log::spy();
        $event = collect(app(Schedule::class)->events())->first(fn ($event): bool => $event->description === 'market-sync');

        $event->finish($this->app, 1);

        Log::shouldHaveReceived('error')->with('market_sync_process_failed', \Mockery::on(fn (array $context): bool => $context['exit_code'] === 1 && $context['reason'] === 'command_failed'))->once();
    }
}
