<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ScoreGeneratorService
{
    public function generate(): array
    {
        try {
            $process = new Process(['python3', base_path('teste.py')]);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = explode("\n", trim($process->getOutput()));

            return [
                'home' => (int) $output[0],
                'away' => (int) $output[1],
            ];
        } catch (\Exception $e) {
            return [
                'home' => rand(0, 7),
                'away' => rand(0, 7),
            ];
        }
    }
}
