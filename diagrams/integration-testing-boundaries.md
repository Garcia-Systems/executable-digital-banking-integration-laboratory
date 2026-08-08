# Integration testing boundaries

```mermaid
flowchart LR
  subgraph A[Scope A — HTTP integration test]
    HT[HTTP request] --> R[Router] --> C[Controller] --> AS[Application service] --> AD[Adapter] --> DT[Deterministic vendor transport]
  end
  subgraph B[Scope B — SQL integration test]
    ST[Test] --> RE[Application / repository] --> DB[(Real local SQLite)]
  end
  subgraph C[Scope C — vendor integration test]
    VT[Test] --> VA[Adapter] --> VC[Real client] --> P[JSON / XML parser] --> VDT[Deterministic transport]
  end
  subgraph D[Scope D — frontend contract test]
    CF[Shared Harbor JSON fixture] --> TV[TypeScript runtime validator]
  end
  NS[Live Northstar]:::outside
  HE[Live Heritage]:::outside
  CV[Live ClearVerify]:::outside
  DT -. outside test boundary .-> NS
  VDT -. never contacted .-> HE
  VDT -. never contacted .-> CV
  classDef outside fill:#eee,stroke:#777,stroke-dasharray:5 5
```

The dashed live systems are deliberately outside every Chapter 19 test scope. A deterministic transport makes request construction observable while preventing a live call.
