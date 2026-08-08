# Integration adapter pattern

```mermaid
flowchart TB
  subgraph Harbor[Harbor Application]
    UseCase[Use Case]
    Port[Harbor-owned Port]
    UseCase --> Port
  end
  Adapter[External Adapter<br/>identity mapping<br/>semantic translation]
  Client[External Client<br/>request construction<br/>response decoding]
  Transport[Transport]
  System[External System]
  Port -. dependency inversion .-> Adapter
  Adapter --> Client --> Transport --> System

  subgraph Examples[Two instances, different capabilities]
    N[DigitalBankingGateway<br/>Northstar Adapter<br/>REST / JSON]
    H[AccountBalanceGateway<br/>Heritage Adapter<br/>SOAP / XML]
  end
```

The dotted edge emphasizes that the adapter implements the Harbor-owned port: the application does not depend on the concrete adapter. Northstar and Heritage reuse this pattern, not one universal business interface.
