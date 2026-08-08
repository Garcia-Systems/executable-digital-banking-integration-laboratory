# Harbor digital banking integration ecosystem

This diagram is the fictional architecture used by the laboratory—not a universal reference architecture for financial institutions.

```mermaid
flowchart LR
    Member((Member))

    subgraph Harbor[Harbor-owned systems]
        Web[Member Web]
        Mobile[Mobile Banking]
        Operations[Internal Operations]
        Integration[Harbor Integration Layer]
        Database[(Member Database)]
    end

    subgraph Vendor[Vendor-owned systems]
        Platform[Vendor Digital Banking Platform]
        Core[Legacy Core Banking System]
    end

    subgraph ThirdParty[Third-party systems]
        Fintech[Third-Party Fintech Provider]
    end

    Member --> Web
    Member --> Mobile
    Web --> Integration
    Mobile --> Integration
    Operations --> Integration
    Integration --> Database
    Integration --> Platform
    Integration --> Core
    Integration --> Fintech

    classDef harbor fill:#d8eef8,stroke:#176b87,color:#102a33
    classDef vendor fill:#fff0c2,stroke:#966b00,color:#332600
    classDef third fill:#eadcf7,stroke:#704095,color:#271533
    class Web,Mobile,Operations,Integration,Database harbor
    class Platform,Core vendor
    class Fintech third
```

The arrows describe laboratory interactions, not every dependency a production institution would require.
