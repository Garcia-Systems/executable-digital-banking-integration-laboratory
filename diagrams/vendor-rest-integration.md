# Vendor REST integration

```mermaid
flowchart TD
    API[Harbor REST API] --> APP[GetMemberSummary]
    APP --> GW[DigitalBankingGateway]
    GW --> ADAPTER[NorthstarDigitalBankingAdapter]
    ADAPTER --> CONTRACT[NorthstarClient]
    CONTRACT --> REST[NorthstarRestClient]
    REST --> HTTP[HttpClient]
    HTTP --> TIMEOUT{{timeout / connection failure}}
    HTTP --> BOUNDARY["======= NETWORK BOUNDARY ======="]
    BOUNDARY --> NS[Northstar REST API]
    NS --> STATUS{{HTTP error}}
    NS --> RESPONSE[HTTP JSON Response]
    RESPONSE --> DECODER[JSON Decoder]
    DECODER --> DECODEFAIL{{decoding error}}
    DECODER --> CUSTOMER[NorthstarCustomer]
    CUSTOMER --> TRANSLATION[Adapter Translation]
    TRANSLATION --> TRANSFAIL{{translation error}}
    TRANSLATION --> MEMBER[Harbor Member]
    MEMBER --> PRESENTER[API Presenter]
    PRESENTER --> JSON[Harbor JSON]
```

The deterministic `HttpClient` occupies the network seam in the laboratory. It returns explicit fixtures or immediately raises a classified failure; it never performs DNS or opens a socket.
