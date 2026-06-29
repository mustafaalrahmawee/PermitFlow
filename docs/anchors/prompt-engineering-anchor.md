# Curated Anchor — Prompt Engineering

## 1. Source Basis

- Berryman, John & Ziegler, Albert, _Prompt Engineering for LLMs — The Art and Science of Building Large Language Model–Based Applications_ (O'Reilly Media, 2025):
  - **Chapter 4 — Document framing and conversion to the model domain**:
    prompt+completion as a continued document, Little Red Riding Hood Principle,
    four criteria for converting a user's problem into the model domain.
  - **Chapter 5 — Clarifying the question, content sources, and few-shot examples**:
    positive over negative instructions, static vs. dynamic content, direct /
    indirect / boilerplate context, few-shot prompting and its drawbacks.
  - **Chapter 6 — Prompt anatomy and the structured-document archetype**:
    introduction / body / refocus / transition, the Valley of Meh, structured
    documents with fixed section sequences.
  - **Chapter 7 — Completion preambles**: structural boilerplate, reasoning
    preamble (long preamble as virtue for chain-of-thought), main answer.
  - **Chapter 8 — Reasoning techniques**: chain-of-thought, plan-and-solve,
    reflexion (orchestration), tool-using agents (out of scope per RC-07/11).
- Read by the author; concepts below are distilled, not summarized from memory.
- Full derivation / reading notes: `docs/anchor-sources/prompt-engineering.md`.

## 2. Provenance Legend

- `[K B 4.x]` / `[K B 5.x]` / `[K B 6.x]` = Direct concept from Berryman, the named chapter.
- `[K B → P]` = Berryman concept, sharpened into a Project decision (applies to all my projects).
- `[K B → A]` = Berryman concept, restructured by the Agent for readability.
- `[A]` = Agent / author suggestion (no direct source).
- `[P]` = Pure Project decision.

## 3. Purpose

This anchor decides **how role files are written** and binds the vocabulary for
any role file consumed by the methodology. A role file is an LLM prompt that
consumes two anchors — a domain anchor plus this prompt-engineering anchor —
together with dynamic input, and produces a single named domain spec.
Philosophy: structured document over conversation; brevity over bulk;
structural stop over token tricks. [K B 4/5/6 → P]

Every instruction states what to do, not what not to do; every output is
anchored on a fixed section skeleton; examples appear only where the skeleton
plus prose leave ambiguity. "Be clear" is not a rule; "open every section with
its focus sentence" is. [P]

**Runtime contexts.** A role file may be consumed in two execution contexts:
(1) **Single-pass chat model** — the model reads the role file once and produces
the spec directly in its text output stream; the preamble (AC-15) and the spec
share one stream.
(2) **Agentic runtime** — the role file is consumed by an agent (Claude Code,
Codex, etc.) that has its own gather-context → take-action → verify-results
loop and native tools. The preamble lives in the gather-context output stream;
the spec is materialised via the runtime's file-write capability as the main
answer.

The anchor binds how role files are **written**; it does not bind which runtime
context they execute in. Both are legitimate. The reasoning-preamble structure
(AC-15) is compatible with both. [K B 7+8 → P]

## 4. Accepted Concepts — Task Clarity (Ch. 4–6)

| #     | Concept                             | Provenance  | Rule for role files                                                                                                                                                                                                                                                                          |
| ----- | ----------------------------------- | ----------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-01 | Prompt + completion form a document | [K B 4]     | The role file is written as a document the model **continues**, not as a command issued to a chat partner. The last token of the prompt is followed by the model's next token as a natural continuation.                                                                                     |
| AC-02 | Little Red Riding Hood Principle    | [K B 4]     | The role file must read like a document type the model has seen many times in training. The introduction names the document type explicitly so the model recognises the frame on the first read.                                                                                             |
| AC-03 | Four-criterion self-check           | [K B 4 → P] | Before publishing a role file, confirm: (i) training-data resemblance, (ii) all relevant info is present, (iii) the prompt conditions toward a solution, (iv) it reaches a natural endpoint. Applied as an author self-check, not as a procedural step inside the role file itself.          |
| AC-04 | Clarification rules                 | [K B 5]     | Prefer positives over negatives; bolster each instruction with its reason; avoid **instructive** absolutes ("always / never") — they reduce robustness and produce inconsistent results across runs. **Structural** absolutes (section-skeleton fixedness, AC-11) are exempt from this rule. |
| AC-05 | Document type lock-in               | [K B 6 → P] | Role files are markdown-medium structured documents with a fixed, named-section sequence. Each section is a function-carrying slot. No other Berryman archetype (advice-conversation, analytic-report) is admitted.                                                                          |

## 5. Accepted Concepts — Input, Output, and Examples

### 5a. Input Specification (Ch. 4–5)

| #     | Concept                         | Provenance  | Rule                                                                                                                                                                                                                                                                                           |
| ----- | ------------------------------- | ----------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-06 | Static vs. dynamic content      | [K B 5 → P] | The role file declares which prose is fixed (static) and which slots are filled at run-time (dynamic). Dynamic content has two forms: pre-loaded files and live elicitation (e.g. a `00-` interview step). The static-vs-dynamic boundary is an authoring decision per role file, not a given. |
| AC-07 | Direct / indirect / boilerplate | [K B 4 → P] | Every input is classified: **direct** (from the user), **indirect** (anchor texts plus upstream domain specs from earlier role files), **boilerplate** (framing glue). **Pipeline composition**: the spec produced by one role file becomes indirect context for a downstream role file.       |
| AC-08 | Brevity / Chekhov's-gun fallacy | [K B 5]     | Include only context that contributes to the deliverable. Adding seemingly-relevant context that isn't used invites hallucination from noise — the model treats every included element as load-bearing.                                                                                        |

### 5b. Output Format Specification (Ch. 4 + Ch. 6 + Ch. 7–8)

| #     | Concept                                            | Provenance    | Rule                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| ----- | -------------------------------------------------- | ------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-09 | Four-part prompt anatomy + Valley of Meh           | [K B 6 → P]   | The role-file prompt is structured as: (1) **introduction** — declares document type and sets focus; (2) **body** — context and instructions; (3) **refocus** — restates the task before production; (4) **transition** — voice-switch from explaining to producing. Critical content (instructions, output format) belongs at start or end; the early middle is the **Valley of Meh**. The focus-opener recurses to sub-sections. _(Berryman describes the anatomy as recommended structure; we operationalise it as a binding authoring rule.)_                                                                                   |
| AC-10 | Structural stop                                    | [K B 4 → P]   | The deliverable is complete when the section skeleton is filled, not when the model emits a particular token. The role file specifies what "skeleton-complete" means; this is the **structural** stop. Distinct from the API `stop` parameter, which is out of scope.                                                                                                                                                                                                                                                                                                                                                               |
| AC-11 | Section-skeleton template                          | [K B 6 → P]   | The role file's output is anchored on a fixed sequence of named sections. This is the load-bearing output mechanism; examples are supplementary. Section names carry function; their order is part of the spec.                                                                                                                                                                                                                                                                                                                                                                                                                     |
| AC-15 | Reasoning preamble (Inner Plan + Chain-of-Thought) | [K B 7+8 → P] | The role file conditions the model to produce a **reasoning preamble** before the spec is delivered. The preamble has two phases: (1) **Inner Plan** — identify required sections, key tradeoffs, derivation order for _this_ role file's deliverable; (2) **Chain-of-Thought** — execute the plan, derive content step by step, surface intermediate decisions explicitly. The preamble precedes the **main answer** (the spec itself, structured per AC-11). Long preambles are a virtue, not a vice, when they support reasoning [K B 7]. Spec delivery uses the runtime's native output mechanism (see Runtime contexts in §3). |

### 5c. Few-Shot Examples (Ch. 5)

| #     | Concept                               | Provenance  | Rule                                                                                                                                                                                                                                                                                  |
| ----- | ------------------------------------- | ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-12 | Few-shot as implicit clarification    | [K B 5]     | A concrete example often clarifies a non-obvious aspect more cheaply than prose. Used selectively, not as the primary instruction mechanism.                                                                                                                                          |
| AC-13 | Three drawbacks of few-shot           | [K B 5]     | (a) Scales poorly — examples consume prompt space. (b) Anchoring bias — the model imitates surface features. (c) Spurious patterns — the model infers rules the author did not intend. Counter-measures: span structural variety including edge cases; shuffle (no natural ordering). |
| AC-14 | Few-shot only for non-obvious aspects | [K B 5 → P] | Use examples only where the section skeleton plus prose instructions leave genuine ambiguity. **Entry-level scope only**: examples cover individual entries (one rule, one entity, one field), never full deliverables. The section skeleton (AC-11) carries the structural load.     |

## 6. Rejected Concepts (Skip List)

| #     | Concept                                           | Provenance  | Why excluded                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| ----- | ------------------------------------------------- | ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RC-01 | Persona-priming as primary craft technique        | [P]         | This anchor does not build on persona-priming ("You are an expert X") as a primary mechanism for shaping output quality. Reason: the methodology relies on explicit criteria, section skeletons (AC-09, AC-11), and reasoning preambles (AC-15) — all of which give the model direct, verifiable instructions rather than asking it to infer behavior from a role label. Persona-priming in role files is **permitted** (not forbidden) but unsupported; its effects are not claimed by this anchor. Persona-via-few-shot examples is independently endorsed under AC-12 (Berryman Ch 5 supports this for consistency in evaluation tasks).                                                 |
| RC-03 | Inline self-check inside the role file            | [K B 8 → P] | Inline self-check directives ("verify your output before producing it") are unreliable: Berryman (Ch 8, _Executing dangerous tools_) notes that models will occasionally do exactly the opposite of such inline directives — _"with a strategy like this, we guarantee that a small portion of the time, the model will do exactly the thing you told it not to do."_ Verification that actually works is **orchestration-level** — a separate LLM pass, a programmatic check, or a Reflexion-style retry loop with external criterion. That verification layer lives in the runtime, not in the role file. The four-criterion author check (AC-03) remains the pre-publication discipline. |
| RC-04 | Negative-constraints craft                        | [K B → P]   | AC-04 governs the positive form. Lists of "don'ts" are not a binding output-shaping mechanism.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| RC-05 | Provider-specific prompt mechanics                | [K B → P]   | XML tags as binding format, ChatML system / user / assistant roles, prefill, RLHF mechanics, the API `stop` sequence, tokenizer quirks (e.g. GPT whitespace / newline handling). Role files are vendor-agnostic markdown.                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| RC-06 | Retrieval / embeddings / RAG / vector stores      | [K B → P]   | RAG-Pipeline-Mechanik (Chunking, Embedding-Provider, Vector-Stores, Retrieval-Strategie, Reranking, Hybrid-Suche) ist **Application-Concern** und kein Authoring-Concern. Auch wenn das konsumierende Projekt RAG implementiert (z. B. dieses Repo als Self-Hosted Knowledge-Hub), bindet dieser Anchor ausschließlich das **Schreiben** der Rolle-Dateien — nicht die Retrieval-Schicht, nicht das Embedding-Caching, nicht die Zitations-Erzwingung zur Laufzeit.                                                                |
| RC-07 | Tool / function-calling definitions in role files | [K B → P]   | Role files do not define their own tools (no JSON schemas, no function declarations, no tool-calling directives in the role-file text). This binds **authoring** only. When a role file is consumed by an **agentic runtime** that has its own native tools, the runtime is free to use them — the anchor neither requires nor forbids runtime tool use. The spec-delivery via the runtime's file-write capability (AC-15, Runtime contexts) is therefore not in tension with this RC.                                                                                                                                                                                                      |
| RC-08 | Summarisation, evaluation                         | [K B → P]   | Out of scope as artifact concerns. Evaluation methodology lives elsewhere.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| RC-09 | Other Berryman archetypes                         | [K B 6 → P] | Advice-conversation and analytic-report archetypes are rejected; document type locked to structured document (AC-05).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| RC-10 | Runtime feedforward pipeline                      | [K B → P]   | Snippetising, scoring / prioritising, eliding, assembly accounting, elastic snippets, prompt-assembly engine, position / importance / dependency as **assembly-time** constructs. Role files are statically authored; runtime mechanics belong to the executing system.                                                                                                                                                                                                                                                                                                                                                                                                                     |
| RC-11 | Application-loop constructs in role files         | [K B → P]   | Role files do not prescribe application loops — no stateful-chat directives, no agentic-loop constructs, no conversation-truncation logic, no Reflexion-pattern definitions. A role file is **authored** as a single static document. When consumed by an agentic runtime with its own loop (gather-context → take-action → verify-results), the role file operates implicitly within that loop without specifying it. Orchestration patterns at workflow level (global plan across multiple role files, Reflexion, multi-file pipelines) are anchor-neutral workflow design.                                                                                                               |
| RC-12 | Application-design context-discovery methodology  | [K B 5 → P] | Mind-map, proximity / stability dimensions, source roadmap. Application-design process, not artifact constraint.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| RC-13 | "No bad ideas" brainstorming advice               | [K B → P]   | Process advice, not a binding rule for what a role file contains.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |

## 7. Application Rules (How to apply these concepts to any role file)

- **State the deliverable as a familiar document type.** The introduction names
  what the role file produces and the form it takes, so the model recognises the
  task on the first read. [K B 4]
- **Pass the four-criterion self-check before publishing.** Training-data
  resemblance, all info present, conditioning toward solution, natural endpoint.
  Author's discipline, not a step inside the role file. [K B 4 → P]
- **Use positives, bolster with reason, avoid instructive absolutes.** Every
  instruction states what to do and why; structural absolutes (section-skeleton
  fixedness) are exempt and explicitly so. [K B 5]
- **Declare static vs. dynamic content; classify every input.** State which
  prose is fixed and which slots are filled at run-time. Mark each input as
  direct, indirect, or boilerplate. Include only what contributes; brevity over
  bulk. [K B 5 → P]
- **Use the four-part anatomy with focus-opener recursion.** Introduction → body
  → refocus → transition, in order. Each named section opens with its focus
  sentence. Critical content sits outside the Valley of Meh. [K B 6 → P]
- **Anchor the output on a section skeleton.** The role file specifies a fixed
  sequence of named output sections; the deliverable is structurally complete
  when the skeleton is filled. This is the load-bearing output mechanism. [K B 6 → P]
- **Few-shot only for non-obvious aspects, entry-level.** Use examples where the
  skeleton plus prose leave genuine ambiguity. Cover individual entries (one
  rule, one field), span structural variety, shuffle. Never give a full-deliverable
  example. [K B 5 → P]
- **Distinguish structural from instructive absolutes.** "Every section has a
  focus sentence" is structural and binding; "always be clear" is instructive
  and forbidden. [P]
- **Open with a reasoning preamble.** Before producing the spec, the role file
  instructs the model to output an **Inner Plan** (sections, tradeoffs,
  derivation order) followed by **Chain-of-Thought** reasoning that executes the
  plan. Both phases live in the preamble; the spec follows as the main answer.
  Long preambles are virtuous when they support reasoning. [K B 7+8 → P]

## 8. Binding Vocabulary

**Use these terms** — the controlled vocabulary of this anchor:

- Methodology: `role file`, `anchor`, `domain spec`, `pipeline composition`, `structured document`, `section skeleton`, `agentic runtime`, `runtime context` [K B → P]
- Task framing: `Little Red Riding Hood Principle`, `document type`, `training-data resemblance`, `natural endpoint` [K B 4]
- Input: `static content`, `dynamic content`, `direct context`, `indirect context`, `boilerplate`, `pre-loaded file`, `live elicitation` [K B 5 → P]
- Output anatomy: `introduction`, `body`, `refocus`, `transition`, `Valley of Meh`, `focus sentence` [K B 6]
- Output structure: `structural stop`, `section-skeleton template`, `entry-level example` [K B 6 → P]
- Few-shot: `few-shot example`, `non-obvious aspect`, `structural variety`, `shuffle` [K B 5]
- Reasoning: `reasoning preamble`, `Inner Plan`, `chain-of-thought`, `main answer` [K B 7+8 → P]

**Forbidden** — any occurrence means the file has drifted into another phase or into excluded scope:

- Runtime mechanics: `prompt assembly engine`, `snippet` (in Berryman's sense), `token budget`, `eliding`, `elastic snippet`, `incompatibility`, `additive / subtractive greedy`
- Provider-specific: `system prompt`, `user message`, `assistant turn`, `prefill`, `stop sequence` (as API parameter), `tokenizer` (as authoring concern)
- Persona / negation craft: bare `you are an expert…`, instructive `do not` / `never` / `always` (structural absolutes are exempt — see AC-04)
- Other archetypes: `analytic report`, `advice conversation`
- Application-design vocabulary: `mind map`, `context proximity`, `context stability`, `source roadmap`

_(A role file may **declare** structural rules ("every section opens with its focus sentence") and **explain** them; it never specifies provider mechanics or runtime assembly. The boundary is: bind the authored prompt, never bind the executing system.)_

## 9. Role Usage Rule

Every role file (e.g. `01-requirements-architect.md`) consumes this anchor as
**binding context**. Berryman Chapters 4–8 are background knowledge, distilled
in `docs/anchor-sources/prompt-engineering.md`; the book is not the direct
prompt source.

Hard rules for every authored role file:

1. The introduction names the document type, what the role produces, and the
   focus — in that order. The Little Red Riding Hood frame is established on
   the first read. [K B 4]
2. The role file declares a section skeleton — a fixed sequence of named
   output sections — and treats skeleton-completion as the deliverable's
   structural stop. [K B 6 → P]
3. Every input is declared static or dynamic, and direct, indirect, or
   boilerplate. Brevity is enforced; unused context is removed. [K B 5 → P]
4. Few-shot examples (if present) are entry-level only, structurally varied,
   and shuffled — never full deliverables. The skeleton, not the examples,
   carries structural load. [K B 5 → P]

---

> **Scope note.** This anchor covers Berryman & Ziegler Chapters 4–8 across
> four pillars (task clarity, input specification, output format including
> reasoning preamble, few-shot examples). Reasoning preamble (Inner Plan +
> Chain-of-Thought) is admitted as the model-output structure preceding the
> spec; orchestration-level techniques (global plan across role files,
> Reflexion, multi-file pipelines) are workflow design and anchor-neutral.
> Out of scope: runtime prompt-assembly machinery, provider-specific
> mechanics, retrieval / RAG, tool / function-calling definitions **inside**
> role files, application-loop constructs **inside** role files, inline
> self-check craft, persona priming. The anchor binds the **writing** of
> statically authored role files; it does not bind the runtime that consumes
> them.
