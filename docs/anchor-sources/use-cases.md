# Cockburn: Writing Effective Use Cases — Chapter 1 Summary

## 1.1 What Is a Use Case (More or Less)

### Core Definition
A use case is a contract between stakeholders about the behavior of a system under discussion (SuD). It describes how the system responds to a request from a primary actor to achieve that actor’s goal while protecting the interests of all other stakeholders. It bundles multiple possible flows (scenarios) into a single coherent narrative.

### The Three Challenges of Writing Use Cases
Reading a use case takes only minutes because it consists of simple grammatical steps (`Actor does X → System responds with Y`). Writing one is difficult because the author must strictly separate three concepts in every sentence and across the entire document:

-   **Scope:** What exactly is the system we are discussing? Where do we draw the boundary?
-   **Primary Actor:** Who has the goal and initiates the interaction?
-   **Level:** How high or low is this goal situated? Does it take minutes or months?

### Vocabulary (Building Blocks)

| Term | Cockburn’s Definition |
| :--- | :--- |
| Actor | Anyone or anything that exhibits behavior (person, external system, hardware). |
| Stakeholder | Someone or something with a legitimate interest in the SuD’s behavior. |
| Primary Actor | The stakeholder who initiates the interaction to achieve a goal. |
| Scope | Defines the system being considered. |
| Preconditions | What must be true *before* the use case starts. |
| Guarantees | What is guaranteed to be true *after* the use case ends (minimal guarantee on failure, success guarantee on completion). |
| Trigger | The event that initiates the use case. |
| Main Success Scenario | The ideal path: nothing goes wrong, the goal is achieved. |
| Extensions | Alternative paths, errors, or special cases. Numbering (e.g., `4a`) refers to the step in the main scenario where the deviation occurs. |

> **Note on Formatting:** When a use case calls or references another use case, the referenced use case name is underlined.

### Two Examples Compared (Scope & Level)
Cockburn demonstrates through two examples that the rules are universal, but scope and level change:

**Example 1: Buying Stocks via the Web (Use Case 1)**
-   **Primary Actor:** A person (the investor).
-   **Goal:** Buy stocks.
-   **Scope:** A computer program named "PAF" on a workstation.
-   **Symbol:** ⬛ Black Box (we are examining a technical software system).
-   **Level:** User Goal (the goal is achieved in a single session at the screen).
-   **Symbol:** 🌊 Sea Level (normal user interaction).

**Example 2: Getting Paid After a Car Accident (Use Case 2)**
-   **Primary Actor:** The injured party (claimant).
-   **Goal:** Receive money from the insurance company.
-   **Scope:** The entire insurance company ("MyInsCo").
-   **Symbol:** 🏢 Building (we are examining an entire organization including its employees and processes).
-   **Level:** Summary (the goal takes days or weeks and requires multiple sessions and departments).
-   **Symbol:** ☁️ Above Sea Level (summarizing, higher-level process).

**Key Lesson:** Whether describing a small software function (⬛🌊) or a massive organizational process (🏢☁️), the grammar and structure of the use case remain identical. Only the level of detail and rigor vary.

### Form, Style, and Cockburn’s Philosophy
-   **Text over diagrams:** Use cases are fundamentally a textual form. Flowcharts or code are possible, but plain text is the best choice because anyone can read and understand it without technical training.
-   **Communication over documentation:** The real value of a use case often lies in sparking team discussion. Whether the team ultimately documents requirements or final design with it is secondary.
-   **"Sufficient" over perfect:** Real-world use cases are rarely formally perfect or complete. They often contain open questions (e.g., `3a1: What does the insurance company do here?`). That is perfectly acceptable. They only need to be "sufficient" to align the team. Even Cockburn admits he rarely writes "perfect" use cases.
-   **Symbols are optional:** Using symbols (🌊, ☁️, ⬛, 🏢) is helpful but not mandatory. However, scope and level must always be explicitly named.

### Theoretical Anchor Takeaway (Section 1.1)
Section 1.1 demystifies use cases. They are not rigid technical specifications but flexible narrative contracts. They force the team to answer three questions before development begins: What is the system boundary? Who wants to achieve what? And at what temporal/organizational level are we operating? Once these three questions are answered, the use case serves as a reliable map for all stakeholders.

---

## 1.2 Your Use Case Is Not My Use Case

### Core Message
There is no single correct way to write use cases. A use case that is perfect for Project A may be completely wrong for Project B. The golden rule is: **"One size doesn’t fit all."**

### Four Dimensions of Adaptation
Every project team must decide where it positions itself across these four dimensions:

1.  **Purpose:** Business Use Case (organizational process), System Use Case (software requirement), or Design Use Case (internal architecture).
2.  **Formality (Dress Level):** Fully Dressed (rigorous, detailed) vs. Casual (informal, brief).
3.  **Goal Level:** Summary (☁️), User Goal (🌊), or Subfunction (🤿).
4.  **Visibility (Box):** Black Box (⬛: What does the system do?) vs. White Box (⬜: How does it do it?).

### Practical Tip: Template vs. Depth
To avoid confusing the team, the following approach has proven effective in practice:

Maintain a consistent template across the entire project (so everyone knows where everything goes), but fill it out to varying depths based on the risk of the specific use case.

-   **Complex Use Case (High Risk):** Fill out all fields meticulously (preconditions, guarantees, multi-page extensions with numbering like `4a`, `4b`).
-   **Simple Use Case (Low Risk):** Use the same template, but for extensions simply write: "No significant alternatives" or "Load error: system shows placeholder."

This saves the team from learning two entirely different forms while still allowing deep exploration of critical topics (e.g., payments) and surface-level treatment of trivial ones (e.g., image zoom). This is the ideal logic for your future AI role.

### Technique vs. Quality vs. Standards
-   **Technique (Craft):** How do I formulate a good sentence? In what order do I proceed? This is what the book teaches. It is universally valid regardless of project size.
-   **Quality:** How do I recognize whether the use case is good enough? This depends on the chosen "dress level."
-   **Standards (Rules):** Which template do we use and how strictly do we review? The project team must decide this themselves.

### Use Cases in Uncharted Territory (Discovery)
When nobody knows what the system should do (as with new printing technology), use cases serve as a discovery tool:
-   Take old processes and ask: "What has become worthless due to the new technology?"
-   Use **Dive-and-Surface**: Sketch roughly (Surface) → drill into details and alternatives (Dive) → adjust the model.

---

## 1.4 When Use Cases Add Value

Use cases deliver value not merely by telling nice stories for users. Their true, measurable value emerges at two specific moments in a project:

### Value Moment 1: The List (Scope & Planning)
The mere act of naming and listing use cases (user goals) creates enormous value.

**Why?** The list defines scope, serves as the basis for cost/time estimates, aids prioritization, and acts as the central communication anchor for all stakeholders.

### Value Moment 2: Failure Scenarios (Extensions & Edge Cases)
The greatest value emerges when brainstorming what can go wrong in the main path (failure conditions).

**Why?** This is exactly where hidden business rules, missing stakeholders, or unknown subsystems are discovered.

**The Danger:** If this is omitted from the use case, programmers will only find these errors late during coding. Domain experts are often no longer available by then, and developers simply guess how to handle the error (leading to poor software).

---

## 1.5 Manage Your Energy (AI Adaptation)

### Core Message
What "energy management" is for humans, **attention and context management** is for an AI. If an AI attempts to generate a complete use case including all error handling in a single step (zero-shot), it loses quality, overlooks edge cases, or hallucinates.

The solution is Cockburn’s 4-stage model, applied as a strict chain-of-thought for the AI agent.

### Principle: Accuracy Before Precision
Before the AI goes into detail (high precision), it must first validate the rough goal (accuracy). This prevents the AI from writing pages of details for a system it has fundamentally misunderstood.

### The 4 Stages as an AI Generation Workflow

| Stage | AI Focus | What the AI May Do (and Must Not Do) |
| :--- | :--- | :--- |
| **1. Actors & Goals** (Scope & Objectives) | Validate system understanding. | ✅ **Do:** Only list actors and their overarching goals.<br>❌ **Forbidden:** Do not write any use case steps yet. |
| **2. Main Success Scenario** (Happy Path) | Establish core logic. | ✅ **Do:** Formulate the ideal flow without errors or exceptions.<br>❌ **Forbidden:** Do not get distracted by failure cases yet. |
| **3. Failure Conditions** (Error Brainstorming) | Analytical thinking (edge cases). | ✅ **Do:** For each step from Stage 2, *only* list what can go wrong.<br>❌ **Forbidden:** Do *not* write solutions yet! (This conserves context window and increases hit rate in error discovery.) |
| **4. Failure Handling** (Error Resolution) | Logical exception handling. | ✅ **Do:** Now formulate the exact system response for each condition found in Stage 3. |

### Theoretical Anchor Takeaway for AI Role
The use case agent must be prompted so that it **never** outputs a use case "all at once." It must guide the user through these 4 stages (or process them internally as intermediate steps).

**ROI:** Stages 3 and 4 are, according to Cockburn, the moments where the true value of a use case emerges (uncovering hidden business rules). By separating *finding errors* (Stage 3) from *resolving errors* (Stage 4), you force the AI into the same depth of analysis as a top-tier business analyst.

---

## 1.6 Warm Up With a Usage Narrative (AI Adaptation)

A usage narrative is a short, highly specific story told from the perspective of a fictional user. It is not a use case; it is the emotional and contextual precursor that grounds the later formal use case. According to Cockburn, the use case itself is merely the "dried-out," generalized form of this narrative.

### Why This Is Critical for Your AI Role
AIs tend to immediately write in abstract, technical, and generic terms. A usage narrative forces the AI, *before* formalization, to:
-   Simulate context & motivation (`Why does the actor want this?`)
-   Think through real usage situations (`When? Where? Under what pressure?`)
-   Uncover implicit requirements (e.g., `Mary uses this ATM because it dispenses $5 bills` → implicit requirement: system must support configurable bill combinations)

This prevents sterile, impractical use cases and ensures the resulting "contract" remains genuinely user-centered.

### The 2-Step Workflow for the AI Agent

**Step 0 – Write the Narrative:** The AI sketches a short paragraph (max. 3–4 sentences) featuring a concrete actor, their motive, and the real-world flow. No templates, no technical jargon.

**Step 1 – Abstract & Formalize:** The AI extracts the use case from the narrative. The concrete name becomes the generic `Primary Actor`, emotions disappear, steps are generalized, and extensions are logically added.

### Theoretical Anchor Takeaway for AI Role
> **Usage Narrative = Context Primer. Use Case = Formalized Contract.**

The AI must first tell the story, then write the contract. The narrative does not serve as a requirements document; it serves as a validation and grounding filter. It ensures the resulting use case is not only syntactically correct but semantically meaningful and user-relevant.