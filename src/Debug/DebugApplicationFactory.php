<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Debug;

/** Outer composition root: selects one isolated fixture without modifying normal wiring. */
final class DebugApplicationFactory
{
    public function __construct(private readonly DebugScenarioCatalog $catalog = new DebugScenarioCatalog()) {}

    public function scenario(string $id): DebugScenario
    {
        // Catalog entries name exactly one replacement component. A fresh immutable
        // scenario is returned on every call, so no injected state crosses runs.
        return $this->catalog->find($id);
    }
}
