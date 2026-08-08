# Digital experience optimization

```mermaid
flowchart TD
  Web[Member Web] --> Events[Safe analytics events]
  Events --> Repo[Deterministic analytics fixture]
  Repo --> Analysis[Funnel / friction analysis]
  Web --> Task[Member task]
  Task --> UX[Navigation / form / content]
  Analysis --> Observation --> Hypothesis[Experience hypothesis] --> Change[Proposed improvement] --> Validation[Tests / future measurement]
  Help[Public help content] --> SEO[SEO metadata / semantic HTML]
  Financial[Member financial pages] --> NoIndex[noindex]
```

Analytics describes behavior without reproducing member financial records. Observations identify where to investigate; they do not establish why behavior differs.
