<?php

namespace App\Services;

use App\Exceptions\ScoreGenerationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ScoreGeneratorService
{
    public function generate(): array
    {
        try {
            $process = new Process([
                config('score_generator.python_binary'),
                config('score_generator.script_path'),
            ]);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = explode("\n", trim($process->getOutput()));

            return [
                'home' => (int) $output[0],
                'away' => (int) $output[1],
            ];
        } catch (\Throwable $e) {
            Log::warning('Falha ao gerar placar via script Python.', [
                'error' => $e->getMessage(),
                'python' => config('score_generator.python_binary'),
                'script' => config('score_generator.script_path'),
            ]);

            if (config('score_generator.fallback_on_failure')) {
                return [
                    'home' => random_int(0, 7),
                    'away' => random_int(0, 7),
                ];
            }

            throw new ScoreGenerationException(
                'Não foi possível gerar o placar da partida.',
                previous: $e
            );
        }
    }
}
