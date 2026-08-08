# Harbor REST API boundary

```mermaid
flowchart TB
    Channels[Member Web / Mobile] -->|HTTP GET| Router
    subgraph Harbor[Harbor REST API]
        Router --> Controller[Member Controller]
        Controller --> Service[GetMemberSummary]
        Service --> Gateway[DigitalBankingGateway]
    end
    Gateway -->|HARBOR / VENDOR BOUNDARY| Adapter[Northstar Adapter]
    Adapter --> Client[Deterministic Northstar Client]
    Client -->|Northstar representation<br/>vendor model| Adapter
    Adapter -->|Harbor Member<br/>domain model| Service
    Service --> Presenter[MemberSummaryPresenter<br/>Harbor API representation]
    Presenter -->|JSON| Channels
```

The three representations are deliberately distinct: the client produces a vendor representation, the adapter produces Harbor domain meaning, and the presenter produces Harbor's public API contract.
