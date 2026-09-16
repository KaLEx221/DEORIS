<?php

namespace App\Services;

use Illuminate\Support\Collection;

final class ModuleRegistry
{
    /**
     * Get all registered modules with their metadata.
     *
     * @return array<string, array{env: string, url: string, legacy: string, label: string}>
     */
    public function all(): array
    {
        return [
            'entryease' => [
                'env' => 'ENTRYEASE_URL',
                'url' => env('ENTRYEASE_URL', 'https://entryease.onrender.com'),
                'legacy' => 'EntryEase',
                'label' => 'EntryEase',
            ],
            'enrollease' => [
                'env' => 'ENROLLEASE_URL',
                'url' => 'https://enrollease.deoris.test',
                'legacy' => 'EnrollEase',
                'label' => 'EnrollEase',
            ],
            'gradetrack' => [
                'env' => 'GRADETRACK_URL',
                'url' => 'https://gradetrack.deoris.test',
                'legacy' => 'GradeTrack',
                'label' => 'GradeTrack',
            ],
            'meditrack' => [
                'env' => 'MEDITRACK_URL',
                'url' => 'https://meditrack.deoris.test',
                'legacy' => 'MediTrack',
                'label' => 'MediTrack',
            ],
            'librarysys' => [
                'env' => 'LIBRARYSYS_URL',
                'url' => 'https://librarysys.deoris.test',
                'legacy' => 'LibrarySys',
                'label' => 'LibrarySys',
            ],
            'taskflow' => [
                'env' => 'TASKFLOW_URL',
                'url' => 'https://taskflow.deoris.test',
                'legacy' => 'TaskFlow',
                'label' => 'TaskFlow',
            ],
            'careerconnect' => [
                'env' => 'CAREERCONNECT_URL',
                'url' => 'https://careerconnect.deoris.test',
                'legacy' => 'CareerConnect',
                'label' => 'CareerConnect',
            ],
            'assesspay' => [
                'env' => 'ASSESSPAY_URL',
                'url' => 'https://assesspay.deoris.test',
                'legacy' => 'AssessPay',
                'label' => 'AssessPay',
            ],
            'votesys' => [
                'env' => 'VOTESYS_URL',
                'url' => 'https://votesys.deoris.test',
                'legacy' => 'VoteSys',
                'label' => 'VoteSys',
            ],
            'clearcheck' => [
                'env' => 'CLEARCHECK_URL',
                'url' => 'https://clearcheck.deoris.test',
                'legacy' => 'ClearCheck',
                'label' => 'ClearCheck',
            ],
        ];
    }

    /**
     * Get module links with resolved URLs (from env or default).
     *
     * @return array<string, array{url: string, label: string}>
     */
    public function links(): array
    {
        $urls = config('modules.urls', []);
        $labels = config('modules.labels', []);

        return collect($this->all())
            ->map(fn (array $module, string $key) => [
                'url' => $urls[$key] ?? $module['url'],
                'label' => $labels[$key] ?? $module['label'],
            ])
            ->all();
    }

    /**
     * Get all module URLs (resolved from config or default).
     *
     * @return array<int, string>
     */
    public function urls(): array
    {
        $configured = config('modules.urls', []);

        return collect($this->all())
            ->map(fn (array $module, string $key) => $configured[$key] ?? $module['url'])
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Get a specific module by key.
     *
     * @return array{env: string, url: string, legacy: string, label: string}|null
     */
    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Get all module keys.
     *
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->all());
    }
}
