# Unit-testing boundaries

```mermaid
flowchart TB
  subgraph P["Unit: PreviewTransfer"]
    Preview[PreviewTransfer] --> Ports[Harbor ports]
    Ports --> FD[Fake Digital Banking]
    Ports --> FB[Recording Balance Fake]
    Ports --> FV[Fake Verification]
  end
  subgraph A["Unit: NorthstarDigitalBankingAdapter"]
    Adapter[Northstar adapter] --> NC[Fake NorthstarClient]
  end
  subgraph C["Unit: NorthstarRestClient"]
    Client[Northstar REST client] --> HTTP[Fake HttpClient]
  end
  subgraph F["Frontend units"]
    Parser[Parser]
    Reducer[Reducer]
    Validator[Validator]
  end
  subgraph I["Outside these unit boundaries — Chapter 19"]
    RealHTTP[Real local HTTP]
    SQL[Real SQL]
    API[Full Harbor API]
  end
```

The replaced collaborator belongs immediately across the meaningful boundary. Replacing every class between the unit and the outside world would hide, rather than clarify, the architecture.
