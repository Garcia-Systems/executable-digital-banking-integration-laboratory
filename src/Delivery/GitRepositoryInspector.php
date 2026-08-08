<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Delivery;

final class GitRepositoryInspector
{
    /** @param null|callable(list<string>):array{output:string,status:int} $runner */
    public function __construct(private readonly string $repository, private $runner = null) {}

    /** @return array{branch:string,clean:bool,changedFiles:list<string>,stagedFiles:list<string>} */
    public function inspect(): array
    {
        $branch = trim($this->run(['git', 'branch', '--show-current']));
        if ($branch === '') throw new \RuntimeException('Git repository has no current branch.');
        $status = $this->run(['git', 'status', '--porcelain=v1']);
        $changed = [];
        foreach (preg_split('/\R/', rtrim($status)) ?: [] as $line) {
            if ($line === '') continue;
            $path = substr($line, 3); if (str_contains($path, ' -> ')) $path = substr($path, strrpos($path, ' -> ') + 4);
            $changed[] = $path;
        }
        $stagedText = $this->run(['git', 'diff', '--cached', '--name-only']);
        $staged = $stagedText === '' ? [] : preg_split('/\R/', trim($stagedText));
        sort($changed); sort($staged);
        return ['branch'=>$branch, 'clean'=>$status==='', 'changedFiles'=>array_values(array_unique($changed)), 'stagedFiles'=>array_values(array_unique($staged))];
    }

    /** @param list<string> $arguments */
    private function run(array $arguments): string
    {
        if ($this->runner !== null) $result = ($this->runner)($arguments);
        else {
            $process = proc_open($arguments, [1=>['pipe','w'], 2=>['pipe','w']], $pipes, $this->repository);
            if (!is_resource($process)) throw new \RuntimeException('Git could not be started.');
            $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]); $result=['output'=>$output, 'status'=>proc_close($process), 'error'=>$error];
        }
        if ($result['status'] !== 0) throw new \RuntimeException('Git inspection failed: '.trim($result['error'] ?? $result['output']));
        return rtrim($result['output']);
    }
}
