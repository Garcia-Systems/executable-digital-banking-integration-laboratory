# Frontend request-state architecture

```mermaid
flowchart TD
    UI[Member Selector / Retry] --> Controller[MemberRequestController / MemberPage]
    Controller --> Sequence[RequestSequence]
    Controller --> Abort[AbortController]
    Controller --> Client[HarborApiClient]
    Client --> API[Harbor REST API]
    API --> Response[API Response]
    Response --> Current{Current request?}
    Current -- YES --> Update[Update state]
    Current -- NO --> Ignore[Ignore stale result]
    Update --> States{Explicit state}
    States --> Loading[Loading]
    States --> Loaded[Loaded / Empty]
    States --> Error[Error]
    Loading --> Render[renderMemberPage]
    Loaded --> Render
    Error --> Render
    Render --> DOM[DOM]
```

`AbortController` asks obsolete transport work to stop. The current-request check remains the correctness boundary even when a transport ignores cancellation.
