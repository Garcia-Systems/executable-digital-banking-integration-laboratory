# End-to-End Digital Banking Integration Laboratory

This is the canonical Volume I system map. Chapter concepts remain separate, composable boundaries rather than collapsing into one application.

```mermaid
flowchart TB
  Member[Member] --> Web[Member Web<br/>TypeScript / mobile-first]
  Web -->|Harbor REST / JSON| API

  subgraph Harbor[Harbor Application]
    API[Router / Controllers] --> Services[Application Services]
    Services --> Domain[Harbor Domain]
    Services --> DBPort[SQL Repository Port]
    Services --> DigitalPort[DigitalBankingGateway]
    Services --> BalancePort[AccountBalanceGateway]
    Services --> VerifyPort[MemberVerificationGateway]
  end
  DBPort --> DB[(Harbor relational<br/>activity database)]
  DigitalPort --> NA[Northstar Adapter] --> NRC[REST Client] -->|REST / JSON| Northstar[Northstar]
  BalancePort --> HA[Heritage Adapter] --> HSC[SOAP Client] -->|SOAP / XML| Heritage[Heritage]
  VerifyPort --> CVA[ClearVerify Adapter] --> CVRC[REST Client] -->|REST / JSON| ClearVerify[ClearVerify]

  Controls[Automated tests · security/trust boundaries<br/>analytics · debugging · deployment readiness]
  Controls -. verifies .-> Harbor
  Controls -. verifies .-> Web
  Controls -. verifies deterministic adapters .-> NA
  Controls -. verifies deterministic adapters .-> HA
  Controls -. verifies deterministic adapters .-> CVA
```

Chapter 0 establishes the ecosystem; Chapters 2–9 establish domain, ports, transports, adapters, failures, and services; Chapters 10–11 place SQL behind a repository; Chapters 12–17 cover the client and trust/data boundaries; Chapters 18–22 supply testing, debugging, delivery, and analytics controls. Chapter 23 composes those established parts.
