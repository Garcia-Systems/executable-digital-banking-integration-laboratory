# Vendor failure handling

```mermaid
flowchart LR
  E[External System] --> C[Client] --> A[Adapter] --> H[Harbor Application] --> P[Public API]
  F["External Failure<br/>timeout / SOAP Fault<br/>malformed payload<br/>unsupported value"] --> V[Client-Specific Failure]
  V --> T[Adapter Failure Translation]
  T --> I[Harbor IntegrationFailure]
  I --> M[Application Error Mapping] --> PE[Public Error]
  I --> D[Operational Diagnostic]
```

The upper path is unchanged success behavior. The lower path contains vendor vocabulary at the adapter and then branches Harbor-owned meaning into a member-safe contract and internal engineering evidence.
