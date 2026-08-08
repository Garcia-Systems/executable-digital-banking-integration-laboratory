# Deterministic laboratory architecture

```mermaid
flowchart TD
    subgraph C1[Chapter 1 — implemented]
        Entry[CLI / Test] --> Fixture[Scenario Fixture]
        Fixture --> Context[Laboratory Context]
        Context --> Clock[Clock / FixedClock]
        Context --> Ids[IdGenerator / SequenceIdGenerator]
        Context --> Vendor[Simulated Vendor State]
    end

    subgraph Future[Future chapters — not implemented]
        Services[Application services]
        Adapters[REST, SOAP, database, queue, and SDK adapters]
        Services --> Adapters
    end

    Context -. controlled dependencies for .-> Services
```

Solid lines show the code-backed fixture infrastructure implemented in Chapter 1. The dotted line marks the intended dependency direction for future application services; those services and adapters are not part of this chapter.
