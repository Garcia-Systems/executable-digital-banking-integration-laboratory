<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Delivery;

final readonly class ChangeSet
{
    /** @param list<string> $files */
    public function __construct(public array $files)
    {
        foreach ($files as $file) {
            if ($file === '' || str_contains($file, "\0")) {
                throw new \InvalidArgumentException('Changed file paths must be non-empty safe strings.');
            }
        }
    }

    /** @return array<string, list<string>> */
    public function classified(): array
    {
        $groups = [];
        foreach (array_values(array_unique($this->files)) as $file) {
            foreach (self::categoriesFor($file) as $category) {
                $groups[$category][] = $file;
            }
        }
        ksort($groups);
        foreach ($groups as &$files) { sort($files); }
        return $groups;
    }

    /** @return list<string> */
    public static function categoriesFor(string $path): array
    {
        if (str_starts_with($path, 'tests/') || preg_match('#(^|/)(test|tests|[^/]+\.test)\.#i', $path)) return ['TESTING'];
        if (str_starts_with($path, 'contracts/api/')) return ['API_CONTRACT'];
        if (str_starts_with($path, 'src/Domain/')) return ['DOMAIN'];
        if (str_starts_with($path, 'src/Application/')) return ['APPLICATION'];
        if (str_starts_with($path, 'src/Integration/')) return ['INTEGRATION'];
        if (str_starts_with($path, 'database/')) return ['DATABASE'];
        if (str_starts_with($path, 'apps/member-web/')) return ['FRONTEND'];
        if (str_starts_with($path, 'public/') || str_starts_with($path, 'src/Http/') || str_starts_with($path, 'src/Api/')) return ['HARBOR_HTTP'];
        if (str_starts_with($path, 'chapters/') || str_starts_with($path, 'docs/') || str_starts_with($path, 'diagrams/') || $path === 'README.md') return ['DOCUMENTATION'];
        if (str_starts_with($path, 'src/Security/')) return ['SECURITY', 'DATA_EXPOSURE'];
        return ['OTHER'];
    }

    /** @return list<string> */
    public function reviewAreas(): array
    {
        $areas = array_keys($this->classified());
        if (array_intersect($areas, ['API_CONTRACT', 'HARBOR_HTTP', 'FRONTEND'])) $areas[] = 'DATA_EXPOSURE';
        if ($this->files !== []) $areas[] = 'TESTING';
        $areas = array_values(array_unique($areas)); sort($areas); return $areas;
    }
}
