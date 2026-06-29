# Kapital 4

## The User’s Problem

Jede Anwendung beginnt mit dem Problem, das ein echter Nutzer lösen will.

Die Kernbotschaft dieses Abschnitts ist: **"Problem" ist nicht gleich "Problem".** Die Probleme variieren drastisch in ihrer Komplexität, und diese Komplexität bestimmt, wie schwer es ist, das Problem in die "Text-Domain" des LLMs zu übersetzen.

Um das messbar zu machen, spannen die Autoren ein Raster aus **vier Dimensionen der Komplexität** auf (siehe Tabelle 4-1 im Buch):

### 1. Medium des Problems (Medium of the problem)

- **Einfach:** Reiner Text. Das ist das natürliche Habitat des LLMs.
- **Komplex:** Sprache am Telefon, komplexe UI-Interaktionen (Klicks, Drag-and-Drop) oder API-Ströme, die die Applikation erst mühsam in Text (Transkripte, JSON) übersetzen muss, bevor das LLM sie versteht.

### 2. Abstraktionsgrad (Level of abstraction)

- **Einfach:** Das Problem ist konkret, klein und klar definiert (z. B. "Korrigiere die Grammatik in diesem Satz").
- **Komplex:** Ein riesiger, abstrakter Problem- und Lösungsraum. Das LLM muss subjektive Wünsche, objektive Limits und komplexe Logik abwägen, um eine Lösung zu finden (z. B. "Plane eine Reise für mich, die meinen Kindern gefällt, aber ins Budget passt").

### 3. Benötigter Kontext (Context required)

- **Einfach:** Der Nutzer liefert alles Nötige direkt im Prompt mit. Das LLM braucht kein externes Wissen.
- **Komplex:** Die Applikation muss massiven externen Kontext heranziehen (Datenbanken, firmeninterne Dokus, externe APIs, Kalender, aktuelle News), weil das LLM dieses Wissen nicht hat oder es veraltet wäre.

### 4. Zustandsbehaftung (Statefulness)

- **Einfach (Stateless):** Jeder API-Call ist ein in sich geschlossenes Problem. Die App merkt sich nichts vom letzten Mal (z. B. ein einfacher Übersetzer).
- **Komplex (Stateful):** Die App muss sich über Wochen an Interaktionen, abgebrochene Versuche, Präferenzen und Kontext erinnern und diesen über mehrere Sessions hinweg verwalten.

### Die drei Beispiele aus dem Buch

Um diese Dimensionen zu verdeutlichen, vergleichen die Autoren drei typische Anwendungen:

1. **Proofreading (Korrekturlesen) – _Niedrige Komplexität_**
   - Medium: Text
   - Abstraktion: Sehr gering (Regelbasiert)
   - Kontext: Nur der Text des Users
   - Statefulness: Null (jeder Satz ist isoliert)

2. **IT-Support-Assistent – _Mittlere Komplexität_**
   - Medium: Text oder Voice
   - Abstraktion: Hoch (großer Lösungsraum, aber durch Tech-Dokus begrenzt)
   - Kontext: Erfordert durchsuchbare technische Dokumentationen und frühere Support-Tickets
   - Statefulness: Muss den bisherigen Chat-Verlauf tracken, um nicht dieselben Fragen zweimal zu stellen.

3. **Reiseplanung (Travel Planning) – _Extrem hohe Komplexität_**
   - Medium: Komplexe UI + Text + externe APIs
   - Abstraktion: Extrem hoch (subjektiver Geschmack vs. objektive Limits)
   - Kontext: Airline-APIs, Hotel-Datenbanken, Regierungs-Warnungen, aktuelle News
   - Statefulness: Trackt die Planung über Wochen, inklusive abgebrochener Idee

## Converting the User’s Problem to the Model Domain

Dieser Schritt wird im Buch als das Herzstück des Prompt Engineerings beschrieben. Es ist der "Feedforward-Pass", in dem die Anwendung das reale Problem des Nutzers in ein Text-Dokument (oder Transkript) übersetzt, das das LLM vervollständigen kann.

Damit diese Übersetzung erfolgreich ist, muss der erstellte Prompt **vier gleichzeitige Kriterien** erfüllen:

### 1. Das Prompt muss dem Trainingsdaten-Muster ähneln (Das "Rotkäppchen-Prinzip")

Das Buch prägt hier das **"Little Red Riding Hood Principle"** (Rotkäppchen-Prinzip): Man darf den Pfad der Trainingsdaten nicht verlassen. Je realistischer und vertrauter das Prompt-Dokument für das Modell aussieht, desto vorhersehbarer und stabiler ist die Vervollständigung.

- Bei **Completion-Modellen** bedeutet das, das Prompt wie einen Computercode, einen Nachrichtenartikel, einen Tweet oder ein Markdown-Dokument aussehen zu lassen.
- Bei **Chat-Modellen** ist das Grundgerüst zwar durch ChatML vorgegeben, aber man sollte dennoch innerhalb der User-Nachrichten bekannte Muster nutzen (z. B. Markdown-Syntax wie `#` für Überschriften, ` ``` ` für Code oder `*` für Listen), um dem Modell die Struktur klar zu machen.

### 2. Das Prompt muss alle relevanten Informationen enthalten

Die Anwendung muss den gesamten Kontext sammeln, der zur Lösung des Problems nötig ist. Die Herausforderung liegt hier im **Filtern**: Wenn man das Prompt mit zu viel nur "irgendwie" relevantem Kontext überflutet, wird das Modell abgelenkt und beginnt, irrelevante Dinge zu halluzinieren. Der gesammelte Kontext muss logisch und gut formatiert in das Dokument integriert werden.

### 3. Das Prompt muss das Modell darauf konditionieren, eine hilfreiche Lösung zu generieren

Es reicht nicht, das Problem zu beschreiben; das Prompt muss das Modell aktiv in die Richtung einer Lösung lenken.

- Bei **Completion-Modellen** ist das schwierig: Man muss dem Modell explizit signalisieren, dass die Problembeschreibung vorbei ist und jetzt die Lösung beginnt (z. B. durch ein Beispiel oder eine spezifische Überschrift).
- Bei **Chat-Modellen** ist dies einfacher, da sie darauf trainiert sind, auf eine User-Nachricht mit einer hilfreichen Assistant-Antwort zu reagieren. Dennoch muss die System-Nachricht oder die Struktur des Transkripts so geformt sein, dass das Modell nicht anfängt, das Problem nur weiter auszuwälzen, sondern es tatsächlich löst.

### 4. Die Vervollständigung muss einen natürlichen Endpunkt haben

Das Modell muss wissen, wann es aufhören soll zu generieren.

- **Chat-Modelle** sind darauf trainiert, nach der Assistant-Nachricht automatisch zu stoppen (oft durch ein spezielles `<|im_end|>` Token).
- **Completion-Modelle** benötigen hier mehr Kontrolle. Man muss entweder im Instruktionstext eine Erwartungshaltung aufbauen (z. B. "die Antwort ist kurz"), oder man definiert ein spezifisches Muster, das auf die Lösung folgt, und nutzt den `stop`-Parameter der API, um die Generierung genau in dem Moment abzubrechen, in dem dieses Muster erscheint.

### Das konkrete Beispiel aus dem Buch: Die "Hausaufgaben"-Reiseempfehlung

Um diese vier Kriterien zu verdeutlichen, zeigen die Autoren ein fiktives Prompt für eine Reise-App, die eine Empfehlung für eine Reise nach Nordkorea geben soll. Das Prompt ist als **Completion-Prompt** aufgebaut, um alle Mechanismen sichtbar zu machen:

**Der Prompt-Text (vereinfacht dargestellt):**

> `# Leisure, Travel, and Tourism Studies 101 - Homework Assignment`
> Provide answers for the following three problems. Each answer should be concise, no more than a sentence or two.
>
> `## Problem 1` What are the top three golf destinations to recommend to customers?
> `## Solution 1` St. Andrews, Scotland; Pebble Beach, California; and Augusta, Georgia, USA are great destinations for golfing.
>
> `## Problem 2` Let's say a customer approaches you to help them with travel plans for Pyongyang, North Korea.
> You check the State Department recommendations, and they advise "Do not travel to North Korea due to the continuing serious risk..."
> You check the recent news and see these headlines: "North Korea fires ballistic missile...", "Five-day COVID-19 lockdown..."
> Please provide the customer with a short recommendation for travel to their desired destination. What would you tell the customer?
> `## Solution 2`

**Wie dieses Beispiel die 4 Kriterien erfüllt:**

1. **Rotkäppchen-Prinzip:** Das Dokument ist als "Hausaufgabe" (Homework Assignment) formatiert, ein Dokumenttyp, den das Modell millionenfach im Training gesehen hat. Es nutzt saubere Markdown-Struktur (`#`, `##`).
2. **Relevante Informationen:** Es injiziert nicht nur die User-Anfrage (Nordkorea), sondern holt aktiv externen Kontext ab (Reisewarnung des Außenministeriums, aktuelle Schlagzeilen) und bettet diese direkt in den Text ein.
3. **Konditionierung zur Lösung:** Es nutzt "Problem 1" und "Solution 1" als Few-Shot-Beispiel. Dies etabliert ein klares Muster: Auf `## Problem N` folgt eine kurze, höfliche `## Solution N`. Wenn das Modell dann bei `## Problem 2` ankommt, "weiß" es durch das Muster, dass es jetzt eine Lösung im gleichen Stil liefern muss, anstatt das Problem weiter zu analysieren.
4. **Natürlicher Endpunkt:** Das Prompt endet mit `## Solution 2`. Sollte das Modell nach der Lösung versuchen, ein erfundenes `## Problem 3` zu generieren, kann die Anwendung den `stop`-Parameter auf `\n#` setzen. Sobald das Modell versucht, eine neue Markdown-Überschrift zu beginnen, wird die Generierung sofort und sauber abgeschnitten.

### Fazit des Abschnitts

Die Autoren betonen, dass Prompt Engineering bei diesem Schritt im Grunde "Dramaturgie" (Playwriting) ist. Man baut ein Dokument oder ein Transkript, das dem Modell durch seine Form, seinen Kontext und seine Struktur keine andere Wahl lässt, als die gewünschte, hilfreiche und begrenzte Antwort zu generieren.

## Using the LLM to Complete the Prompt

Die Kernaussage dieses Abschnitts ist: **Es ist ein Trugschluss zu glauben, dass nach dem Bauen des Prompts keine weiteren Entscheidungen mehr anstehen** ("einfach absenden und warten"). Die Autoren betonen, dass nicht alle Modelle gleich sind und der Entwickler hier drei kritische Abwägungen treffen muss:

### 1. Modellgröße: Qualität vs. Kosten

Größere Modelle liefern in der Regel höhere Qualität bei den generierten Texten, aber das hat einen massiven Preis.

- **Beispiel aus dem Buch:** Zum Zeitpunkt der Buchautoren war das Ausführen von **GPT-4** etwa **20-mal teurer** als das Ausführen von **gpt-3.5-turbo**. Der Entwickler muss sich hier die Frage stellen: Ist der Qualitätsgewinn den enormen Preisanstieg um eine ganze Größenordnung wert? Manchmal ja, aber es ist eine bewusste Abwägung.

### 2. Latenz: Rechenzeit vs. Nutzererwartung

Größere Modelle benötigen mehr Rechenleistung, und mehr Rechenleistung bedeutet mehr Zeit. Wenn das Modell zu lange braucht, verlieren die Nutzer die Geduld.

- **Beispiel aus dem Buch:** In den frühen Tagen von **GitHub Copilot** entschieden sich die Entwickler bewusst für ein kleineres OpenAI-Modell namens **Codex**. Es war "klein, ausreichend schlau und blitzschnell". Hätten sie stattdessen GPT-4 verwendet, hätten die Nutzer kaum geneigt gewesen, auf die Vervollständigung zu warten – völlig unabhängig davon, wie gut das Ergebnis am Ende gewesen wäre.

### 3. Fine-Tuning: Basis-Wissen vs. spezifisches Verhalten

Manchmal reicht das allgemeine Wissen des Basis-Modells nicht aus. Die Autoren raten dazu, zu prüfen, ob man durch Fine-Tuning (das gezielte Weitertrainieren eines Modells auf einem spezifischen Datensatz) bessere Ergebnisse erzielen kann. Fine-Tuning ist besonders dann nützlich, wenn das Modell Informationen liefern soll, die in den öffentlichen Trainingsdaten nicht vorhanden waren, oder wenn es ein Verhalten zeigen soll, das vom ursprünglichen Modell abweicht.

- **Beispiel aus dem Buch:** Das Team bei GitHub experimentierte damit, **Codex-Modelle per Fine-Tuning** anzupassen, um **für weniger verbreitete Programmiersprachen** (less common languages) höhere Qualität zu erzielen, da das Basis-Modell für diese Sprachen aufgrund fehlender Trainingsdaten schwächer war.

### Fazit des Abschnitts

Der Abschnitt macht deutlich, dass der "Feedforward-Pass" nicht mit dem Text des Prompts endet. Die Auswahl des richtigen Modells – balanciert zwischen Intelligenz (Größe), Kosten, Geschwindigkeit (Latenz) und der Notwendigkeit von Fine-Tuning – ist ein integraler Bestandteil des Prompt Engineerings und des Designs von LLM-Anwendungen.

## Transforming Back to User Domain

Nachdem das Modell eine Completion (einen Text-Blob) generiert hat, muss die Anwendung diesen Text zurück in die Welt des Nutzers übersetzen.

Die Kernaussage: **Die Completion allein ist fast nie die fertige Lösung.** Sie ist nur ein Zwischenschritt. Die Anwendung muss den Text verarbeiten, extrahieren oder umwandeln, damit er für den Endnutzer nützlich wird.

### 1. Parsen und Strukturieren (bei Completion-Modellen)

Bei den älteren Completion-Modellen war der Rückweg mühsam. Die Anwendung musste das Modell zwingen, die Antwort in einem **sehr spezifischen Format** zu generieren, damit die Anwendung die Informationen danach maschinell herausparsen konnte.

- **Beispiel aus dem Buch:** Man bittet das Modell, ein Dokument zu lesen und daraus tabellarische Informationen zu extrahieren. Das Modell liefert eine formatierte Tabelle als Text zurück, und die Anwendung parst diese Tabelle, um sie dem Nutzer als strukturierte Daten anzuzeigen.

### 2. Function Calling — Der elegante Weg

Mit dem Aufkommen von **Function-Calling-Modellen** wurde die Rücktransformation deutlich einfacher. Das Modell generiert keinen rohen Text mehr, sondern einen **strukturierten Funktionsaufruf** (eine Art maschinenlesbaren Handlungswunsch).

- **Beispiel aus dem Buch (Lesen):** In einer Reise-App übergibt man dem Modell Funktionen, die Flugdaten abfragen können. Das Modell generiert dann keinen langen Text wie "Ich empfehle Ihnen Flug XY...", sondern einen Funktionsaufruf: _"Suche Flüge von Berlin nach New York am 15. Mai"_. Die Anwendung fängt diesen Aufruf ab, fragt die echte Airline-API ab, und präsentiert dem Nutzer die gefundenen Flüge — zurück in der Nutzerdomäne.

- **Beispiel aus dem Buch (Schreiben):** Man geht noch einen Schritt weiter und gibt dem Modell Funktionen, die **reale Veränderungen** auslösen, z. B. _"Kaufe Ticket"_. Wenn das Modell diesen Funktionsaufruf generiert, hakt die Anwendung beim Nutzer nach ("Wirklich buchen?"), und führt dann die Transaktion aus. So wurde aus einem Text-Token eine reale Handlung in der Welt des Nutzers.

### 3. Wechsel des Kommunikations-Mediums

Oft ist die Domäne des Nutzers gar kein Text. Dann muss die Text-Completion in ein **ganz anderes Medium** umgewandelt werden.

- **Beispiel aus dem Buch (Sprache):** Wenn der Nutzer mit einem automatisierten Tech-Support am **Telefon** spricht, muss die Text-Completion des Modells in **Sprache (Speech)** umgewandelt werden.
- **Beispiel aus dem Buch (UI):** Wenn der Nutzer eine komplexe Anwendung mit grafischer Oberfläche bedient, kann die Completion **UI-Events** darstellen, die Elemente der Oberfläche verändern (z. B. ein Dialogfenster öffnen oder eine Liste aktualisieren).

### 4. Änderung der Präsentation (selbes Medium, andere Form)

Selbst wenn die Domäne des Nutzers Text/Code ist, muss die **Präsentation** der Completion oft angepasst werden, damit sie sinnvoll wirkt.

- **Beispiel aus dem Buch (GitHub Copilot):**
  - Bei der **Code-Completion** (automatische Vervollständigung im Editor) wird der generierte Code als **ausgegraute Textzeile** dargestellt. Der Nutzer akzeptiert sie mit der Tab-Taste.
  - Bei **Copilot Chat** (wenn man das Modell explizit um eine Code-Änderung bittet) wird das Ergebnis als **Red/Green-Diff** dargestellt — also mit farblich markierten Entfernungen und Ergänzungen, wie ein klassischer Versionskontroll-Unterschied.

### Fazit des Abschnitts

Der Abschnitt macht deutlich, dass der LLM-Loop nicht mit der Completion endet. Die Anwendung ist eine **Transformations-Schicht in beide Richtungen**:

1. **Vorwärts (Feedforward):** Nutzerproblem → Text-Domäne des Modells
2. **Rückwärts (Transform back):** Text-Completion → Lösung in der Nutzerdomäne

Je nach Anwendungsfall bedeutet das: Parsen, Funktionsaufrufe ausführen, das Medium wechseln (Text zu Sprache oder UI) oder die Präsentation anpassen (grauer Text vs. farbiger Diff). Das Ziel ist immer, die rohe Textausgabe des Modells in eine Form zu bringen, die der Nutzer in seinem eigenen Kontext direkt verwenden kann.

## Building the Basic Feedforward Pass

Der **Feedforward-Pass** ist die zentrale Pipeline, in der die Anwendung das reale Problem des Nutzers in ein Textdokument übersetzt, das das LLM vervollständigen kann. Er besteht aus vier aufeinanderfolgenden Schritten:

### 1. Context Retrieval (Kontext-Beschaffung)

Der erste Schritt ist das Sammeln des Rohmaterials. Die Anwendung muss entscheiden, welche Informationen zur Lösung des Problems nötig sind, und sie abrufen. Die Autoren unterscheiden zwei Quellen:

- **Direkter Kontext:** Kommt unmittelbar vom Nutzer. Im Buch werden zwei Beispiele genannt: der Text, den ein Nutzer in ein Tech-Support-Feld tippt, oder der Code-Block, den ein Entwickler gerade in GitHub Copilot bearbeitet.
- **Indirekter Kontext:** Muss von externen Quellen abgerufen werden.
  Im Prompt wird später **Boilerplate-Text** (vorformuliertes Gerüst) als "Klebstoff" verwendet, um diese Kontextteile logisch zu verbinden. Das Buch illustriert dies am Beispiel einer Reise-App: Neben der direkten Nutzeranfrage ("Empfehlung für Pjöngjang") ruft die Anwendung Reisewarnungen des Außenministeriums und aktuelle Nachrichtenschlagzeilen ab und fügt sie in das Prompt-Gerüst ein.

### 2. Snippetizing Context (Kontext-Zerlegung)

Sobald der Kontext beschafft ist, darf er nicht ungefiltert in den Prompt geladen werden. Er muss **snippetisiert** werden: Das bedeutet, große oder unstrukturierte Textmengen in die kleinsten, relevantesten Brocken zu zerlegen.

- **Beispiel aus dem Buch:** Ein IT-Support-Assistent durchsucht die Dokumentation und erhält seitenlange Suchergebnisse. Würde man alles übernehmen, würde das Token-Limit gesprengt und das Modell abgelenkt. Stattdessen extrahiert die Anwendung nur die relevanten Passagen.
  Manchmal bedeutet Snippetizing auch, nicht-textuelle Kontextinformationen (z. B. Metadaten oder UI-Zustände) erst in lesbare Text-Snippets umzuwandeln.

### 3. Scoring and Prioritizing Snippets (Bewerten und Priorisieren)

Nicht alle Snippets sind gleich wichtig. Um innerhalb des begrenzten Kontext-Fensters die beste Auswahl zu treffen, bewertet die Anwendung jeden Snippet. Die Autoren definieren zwei klar getrennte Mechanismen:

- **Prioritäten (Priorities):** Ganzzahlen, die Hierarchie-Stufen (Tiers) bilden. Bei der Assembly werden zuerst _alle_ Snippets einer höheren Prioritätsstufe eingebaut. Erst wenn noch Platz ist, greift man auf die nächste Stufe zurück.
- **Scores:** Gleitkommazahlen, die feine Unterschiede **innerhalb** derselben Prioritätsstufe abbilden. Selbst wenn zwei Snippets dieselbe Priorität haben, zeigt der Score, welcher relevanter ist und zuerst verwendet werden sollte.

### 4. Prompt Assembly (Prompt-Zusammenbau)

Im letzten Schritt werden die bewerteten Snippets, der Boilerplate-Text und die Nutzeranfrage zum finalen Prompt zusammengesetzt. Hier betreibt der Entwickler "Buchhaltung" (Accounting):

- **Token-Budget prüfen:** Es muss sichergestellt werden, dass Boilerplate, Nutzeranfrage und Kontext-Snippets gemeinsam ins Kontext-Fenster passen.
- **Letzte-Minute-Kürzung:** Falls das Budget knapp wird, muss die Anwendung im Assembly-Schritt aktiv kürzen. Das Buch nennt zwei Methoden: **Eliding** (gezieltes Entfernen weniger relevanter Codezeilen oder Textpassagen) oder **Summarization** (automatisches Zusammenfassen langer Dokumente).
- **Reihenfolge & Struktur:** Die Teile müssen in einer logischen Abfolge platziert werden. Der finale Prompt muss am Ende so aussehen und lesen, als stamme er direkt aus den Trainingsdaten des Modells. Die Autoren verweisen hier erneut auf das **Little Red Riding Hood Principle**: Nur wenn der Prompt einem vertrauten Dokumentmuster folgt, bleibt das Modell auf dem "Pfad" und generiert die gewünschte Completion, statt abzuschweifen.

### Zusammenfassung

Der Basic Feedforward Pass ist eine deterministische Vorverarbeitungspipeline:
`Sammeln` → `Zerlegen` → `Bewerten` → `Zusammenbauen`.
Das Ziel ist ein maximal informativer, aber strikt budgetierter Prompt, der das Modell durch bekannte Strukturen und klare Kontext-Gewichtung zuverlässig zur Lösung führt.

## Exploring the Complexity of the Loop

Dieser Abschnitt beschreibt, wie man von der einfachsten Form einer LLM-Anwendung (eine einzige Anfrage, eine Antwort, kein Gedächtnis) zu komplexen, produktionsreifen Systemen übergeht. Die Komplexität wächst dabei entlang von vier zentralen Dimensionen:

### 1. Persisting Application State (Anwendungsstatus beibehalten)

Einfache Anwendungen (wie die Code-Vervollständigung in GitHub Copilot) sind **zustandslos (stateless)**. Sie nehmen einen Input, senden ihn an das Modell und vergessen ihn sofort wieder.
Komplexere Anwendungen (wie ein Chat-Assistent) müssen den Zustand zwischen mehreren Anfragen beibehalten.

- **Die Herausforderung:** Wenn ein Nutzer eine neue Nachricht sendet, muss die Anwendung den bisherigen Gesprächsverlauf aus einer Datenbank abrufen und als zusätzlichen Kontext in den nächsten Prompt einfügen.
- **Die Lösung bei Langzeit-Interaktionen:** Wenn der Verlauf zu lang wird und das Token-Limit sprengt, kann man ihn nicht einfach immer weiter anhängen. Die Autoren nennen zwei Strategien:
  1. **Truncating (Abschneiden):** Die ältesten Teile des Gesprächs werden einfach entfernt.
  2. **Summarizing (Zusammenfassen):** Wichtige frühere Teile des Gesprächs werden vom LLM oder einem separaten Prozess zusammengefasst, um den Kernkontext zu bewahren, ohne das Token-Budget zu sprengen.

### 2. External Context (Externer Kontext)

LLMs wissen nur das, was in ihren öffentlichen Trainingsdaten stand. Sie kennen keine aktuellen Ereignisse, keine privaten Unternehmensdokumente und keine persönlichen Daten. Wenn man sie nach etwas fragt, das sie nicht wissen, ist es besser, wenn sie sich entschuldigen, anstatt selbstbewusst zu halluzinieren.

- **Die Lösung:** **Retrieval Augmented Generation (RAG)**. Externe Daten werden indiziert (z. B. in Vector Stores oder klassischen Suchmaschinen wie Elasticsearch), damit sie bei Bedarf abgerufen werden können.
- **Das Spektrum der Abfrage-Strategien:**
  1. **Einfach:** Die rohe Nutzeranfrage wird direkt als Suchbegriff an die Datenbank gesendet. (Problem: Lange, unstrukturierte Anfragen führen zu schlechten Suchergebnissen).
     (besser): Man lässt das LLM die Nutzeranfrage lesen und einen optimierten, präzisen Suchbegriff generieren, der dann an die Datenbank gesendet wird.
  2. **Fortgeschritten (Tool-Integration):** In langen Chats ist oft unklar, _ob_ überhaupt eine Suche nötig ist. Hier gibt man dem LLM ein "Such-Tool". Das Modell entscheidet dann selbstständig, _wann_ es suchen muss und _welche_ Begriffe es verwendet.

### 3. Increasing Reasoning Depth (Steigerung der Reasoning-Tiefe)

Größere Modelle können Muster verallgemeinern (z. B. einen Text zusammenfassen, wenn man einfach nur `TL;DR` anhängt, oder übersetzen, wenn man ein einziges Beispiel gibt). Um jedoch wirklich komplexe, mehrstufige Probleme zu lösen, muss man das Modell zu tieferem "Nachdenken" zwingen.

- **Die Methode:** **Chain-of-Thought Prompting** (Schritt-für-Schritt-Denken). Man weist das Modell explizit an, seinen Gedankengang zu zeigen, _bevor_ es die endgültige Antwort gibt.
- **Warum das mechanisch funktioniert (wichtig!):** Die Autoren betonen, dass LLMs kein Bewusstsein und keinen "inneren Monolog" haben. Sie generieren Token rein mechanisch basierend auf den vorherigen Token. Wenn das Modell also "nachdenken" soll, muss es diesen Denkprozess **"laut" (out loud)** in der Text-Completion niederschreiben. Sobald diese Zwischenschritte als Text vorliegen, berechnet das Modell die nächsten Token konsistent mit diesem etablierten "Gedankengang". Das führt nachweislich zu viel besseren und logischeren Endergebnissen.

### 4. Tool Usage (Nutzung von Werkzeugen)

Von sich aus agieren LLMs in einer geschlossenen Welt: Sie können nichts in der realen Welt verändern und keine aktuellen Daten abrufen. Tools durchbrechen diese Grenze.

- **Der Mechanismus:** Im Prompt werden dem Modell eine oder mehrere Funktionen zur Verfügung gestellt (mit Name, Argumenten und einer Beschreibung, was sie tun). Während des Gesprächs kann das Modell entscheiden, eine dieser Funktionen aufzurufen, indem es einen strukturierten Funktionsaufruf generiert.
- **Die Rolle der Anwendung:** Da das LLM selbst keinen Code ausführen kann, muss die **Anwendung** diesen Funktionsaufruf abfangen. Die Anwendung führt dann die echte API-Anfrage in der realen Welt aus, wartet auf die Antwort und fügt dieses Ergebnis in den _nächsten_ Prompt ein. So kann das Modell mit den neuen, realen Daten weiterreasonieren.
- **Read-only vs. Write:**
  - _Read-only:_ Wetter abfragen, E-Mails prüfen (überschneidet sich stark mit RAG).
  - _Write (Schreibend):_ Einen Flug buchen, einen Pull-Request im Code erstellen, eine Datenbank aktualisieren.
- **Die Warnung der Autoren:** Modelle sind probabilistisch und machen Fehler. Man darf einer LLM-Anwendung nicht blind erlauben, reale Schreib-Aktionen (wie das Buchen einer Reise) auszuführen, nur weil der Nutzer es "irgendwann mal" erwähnt hat. Hier sind Sicherheitsvorkehrungen (z. B. menschliche Bestätigung) zwingend erforderlich.

### Fazit des Abschnitts

Der einfache "Feedforward-Pass" ist nur der Ausgangspunkt. Echte LLM-Anwendungen erfordern es, diesen Loop zu erweitern: durch Gedächtnis (State), durch Anbindung an die Außenwelt (RAG/Tools) und durch die Erzeugung von Zwischenschritten (Chain-of-Thought), um die mechanischen Grenzen der Token-Vorhersage in robuste Problemlösungsstrategien zu verwandeln.

## Offline Evaluation

### Das Ziel der Offline-Evaluation

Offline-Evaluation dient dazu, neue Ideen und Änderungen an einer LLM-Anwendung zu testen, **bevor** sie echten Nutzern in der Produktion ausgesetzt werden. Da es zu diesem Zeitpunkt noch keine echten Kunden gibt, die "gut" oder "schlecht" sagen können, muss die Anwendung einen simulierten Proxy (Ersatz) für dieses Nutzerfeedback finden.

Die Autoren unterscheiden dabei zwei grundlegend verschiedene Szenarien:

### 1. Der "glückliche" Fall: Objektiv messbare Ergebnisse

Manchmal lässt sich die Qualität einer LLM-Ausgabe sehr einfach und objektiv überprüfen.

- **Beispiel aus dem Buch (GitHub Copilot):** Bei Code-Vervollständigungen ist ein guter Proxy für die Zufriedenheit der Nutzer, ob der generierte Code funktional und vollständig ist. Man kann funktionierenden Code fragmentweise löschen, das LLM die Lücke füllen lassen und dann automatisch prüfen, ob die Software-Tests immer noch bestehen. Wenn die Tests bestanden werden, ist die Vervollständigung korrekt, und man kann davon ausgehen, dass die Nutzer in der Produktion zufrieden sein werden. Dies ist eine zuverlässige, automatisierbare Metrik.

### 2. Der "schwierige" Fall: Subjektive oder offene Aufgaben

Oft ist man nicht so glücklich. Wie bewertet man einen Terminplanungs-Assistenten, der reale Interaktionen auslösen soll, oder einen allgemeinen Chat, der offene Dialoge führt? Hier gibt es keine einfachen "Bestanden/Nicht bestanden"-Tests.

- **Die Lösung aus dem Buch:** Man setzt ein **LLM als "Judge" (Bewerter)** ein. Man lässt ein LLM (ähnlich wie einen menschlichen Bewerter) Chat-Transkripte oder verschiedene Antwort-Varianten reviewen und entscheiden, welche Variante besser ist.
- **Verfeinerung:** Anstatt das LLM nur pauschal zu fragen "Welche Version ist besser?", kann man dem LLM-Judge eine **Checkliste mit spezifischen Kriterien** geben, die es für jede Variante überprüfen soll. Dies führt zu einer viel nuancierteren und aussagekräftigeren Bewertung.

### Die goldene Regel der Offline-Evaluation

Die Autoren geben eine dringende Warnung am Ende dieses Abschnitts: **Beziehe immer so viel wie möglich der _gesamten_ Anwendung in die Evaluation ein.**

- **Die Falle:** Es ist oft verlockend und einfacher, den komplexen Schritt des "Context-Gathering" (Kontextbeschaffung) zu mocken (also vorzutäuschen oder zu simulieren) und nur die Prompt-Zusammenstellung und das Boilerplate-Textgerüst zu testen. Manchmal ist das Mocken sogar unvermeidbar.
- **Die Warnung:** Wenn man Schritte wie die Kontextbeschaffung oder andere Teile der Anwendung umgeht, gefährdet man die Qualitätssicherung. Oft sind genau diese vorgelagerten Schritte (wie das Finden der _richtigen_ Dokumente für den Prompt) entscheidend für eine hochwertige LLM-Anwendung. Wer sie im Test auslässt, muss mit "bösen Überraschungen" rechnen, sobald das neue Feature in die Produktion geht.

### Zusammenfassung

Offline-Evaluation ist das Testen im Labor. Wenn möglich, nutzt man objektive, automatisierte Tests (wie Code-Tests). Wenn die Aufgabe subjektiv ist, nutzt man ein LLM als Judge mit einer Kriterien-Checkliste. In jedem Fall muss der Test so nah wie möglich am echten, vollständigen Anwendungsablauf (inklusive Kontextbeschaffung) bleiben, um valide Ergebnisse zu liefern.

## Online Evaluation

Der Abschnitt **"Online Evaluation"** (Online-Bewertung) beschreibt, wie man die Qualität einer LLM-Anwendung misst, **nachdem** sie in der Produktion live ist und echte Nutzer sie verwenden. Im Gegensatz zur Offline-Evaluation (Labor-Tests vor dem Release) hat man hier echtes Nutzerverhalten zur Verfügung — aber die Herausforderung ist, die richtigen Signale aus diesem Verhalten herauszufiltern.

### Das Grundproblem: Explizites Feedback ist unzuverlässig

Der offensichtlichste Weg, Nutzerfeedback zu sammeln, ist, sie direkt zu fragen — z. B. durch **Thumbs-up/Thumbs-down-Buttons** neben jeder Antwort (wie in ChatGPT). Die Autoren warnen jedoch vor mehreren Problemen:

- **Bias:** Oft voten nur die wirklich wütenden oder frustrierten Nutzer. Zufriedene Nutzer klicken einfach weiter, ohne zu bewerten.
- **Geringer Traffic:** Selbst bei aktiven Nutzern interagiert nur ein winziger Bruchteil proportional mit den Bewertungsbuttons.
- **Statistische Unschärfe:** Wenn die Anwendung nicht extrem hohen Traffic hat, sammelt man über explizite Buttons nicht genug Daten für verlässliche Aussagen.

Die Schlussfolgerung: Man muss **kreativer** werden und nach **impliziten Qualitätsindikatoren** suchen.

### Implizites Feedback: Das Verhalten der Nutzer messen

Statt zu fragen, was der Nutzer denkt, misst man, **was der Nutzer tatsächlich tut**. Die Autoren nennen zwei konkrete Beispiele:

### Beispiel 1: GitHub Copilot (Code-Vervollständigung)

Hier misst man zwei Dinge:

1. **Akzeptanzrate:** Wie oft drückt der Nutzer die Tab-Taste und übernimmt den generierten Code?
2. **Nachträgliche Änderungen:** Wenn der Nutzer den Code akzeptiert, aber sofort danach zurückgeht und ihn verändert, war die Vervollständigung wahrscheinlich nicht gut genug.

### Beispiel 2: Ein Scheduling-Assistent (Terminplaner)

Hier ist die Metrik weniger offensichtlich. Man könnte versucht sein, die **Session-Länge** zu messen (wie lange der Nutzer mit dem Assistenten interagiert). Aber das ist **mehrdeutig**:

- Ein Nutzer, der schnell fertig wird, könnte **effizient** seine Termine geplant haben (gut!).
- Ein Nutzer, der schnell abbricht, könnte **frustriert** sein und die Erfahrung aufgeben (schlecht!).

Die Autoren raten daher: **Miss etwas, das wirklich zählt.** Für einen Scheduling-Assistenten sind das:

- **Erfolgreich erstellte Kalenderereignisse** (hat der Nutzer sein Ziel erreicht?)
- **Wie oft der Nutzer die Details des Ereignisses nachträglich ändert** (war der Vorschlag des Assistenten präzise genug?)

### Die Goldene Regel: "Measure Something That Matters"

Die Autoren formulieren ein klares Prinzip:

> **Miss etwas, das eine Produktivitätssteigerung für deine Kunden demonstriert.**

Eine Metrik ist nur dann sinnvoll, wenn sie direkt mit dem **echten Nutzen** korreliert, den der Nutzer aus der Anwendung zieht.

- **Copilot** wählte die Akzeptanzrate, weil sie am stärksten mit den Produktivitätsgewinnen der Entwickler korrelierte.
- **Ein Scheduling-Assistent** sollte nicht Session-Länge oder "Anzahl der Nachrichten" messen, sondern konkrete, abgeschlossene Aufgaben (erstellte Termine) und deren Qualität (nachträgliche Änderungen).

### Die Warnung: Implizites Feedback richtig interpretieren

Die Autoren geben eine wichtige Warnung mit auf den Weg: **Sei vorsichtig, wie du implizites Feedback interpretierst.** Das gleiche Nutzerverhalten kann zwei völlig gegensätzliche Bedeutungen haben.

Das Paradebeispiel ist die **Session-Länge**:

- **Positive Interpretation:** Der Nutzer hat seine Aufgabe schnell und effizient erledigt → die App ist hervorragend.
- **Negative Interpretation:** Der Nutzer war frustriert und hat die App vorzeitig verlassen → die App ist miserabel.

Ohne zusätzliche Kontext-Metriken (wie z. B. "wurde die Aufgabe tatsächlich abgeschlossen?") ist eine einzelne implizite Metrik oft nicht aussagekräftig genug.

### Zusammenfassung

Online Evaluation ist das Messen von Qualität in der realen Welt mit echten Nutzern. Die Kernaussagen des Abschnitts sind:

1. **Explizites Feedback** (Buttons, Umfragen) ist anfällig für Bias und liefert oft zu wenig Daten.
2. **Implizites Feedback** (Akzeptanzraten, nachträgliche Änderungen, abgeschlossene Aufgaben) ist aussagekräftiger, erfordert aber sorgfältiges Design.
3. **Die Metrik muss am echten Nutzen koppeln** ("Measure something that matters") — nicht an sekundären Signalen wie Session-Länge oder Nachrichtenanzahl.
4. **Fehlinterpretation ist eine reale Gefahr** — das gleiche Verhalten kann Effizienz oder Frustration bedeuten. Man muss immer prüfen, ob die Metrik die tatsächliche Produktivitätssteigerung widerspiegelt.

# Kapital 5

## Sources of Content

Beim Erstellen eines Prompts gilt in der ersten Phase der Grundsatz „there are no bad ideas“. Das Ziel ist es, zunächst so viele relevante Informationen wie möglich zu sammeln. Das Filtern und Reduzieren dieser Inhalte (Triage) findet erst in einem späteren Schritt statt.

Um diese Informationsquellen systematisch zu erfassen, zieht das Buch die wichtigste Unterscheidung im Prompt-Engineering: die Trennung zwischen **Static Content** und **Dynamic Content**.

### Static Content

Static Content ist immer gleich (always the same). Er erklärt dem LLM die allgemeine Aufgabe, klärt die Fragestellung und gibt präzise Anweisungen.

- **Beispiel aus dem Buch:** Die Anfrage _„Which book do you think I should read next? I mean for fun, not what kind of textbook.“_ Der erste Satz formuliert die allgemeine Frage, der zweite Satz ist die statische Klarstellung (Clarification), die dem Modell sagt, was genau die Aufgabe ist.

### Dynamic Content

Dynamic Content ist jedes Mal anders (different every time). Er liefert den Kontext für das Objekt der Frage, also die Details dessen, wonach konkret gefragt wird.

- **Beispiel aus dem Buch:** Die Anfrage _„Which book do you think I should read next? The last book I read was 'Moby Dick,' btw.“_ Der erste Satz ist wiederum der statische Kontext (die allgemeine Frage). Der zweite Satz liefert den dynamischen Kontext, also die Information, die das Modell benötigt, um die Aufgabe für diesen spezifischen Fall zu lösen.

### Die Grauzone: Statisch vs. Dynamisch

Das Buch betont, dass diese beiden Arten von Inhalten in der Praxis nicht immer sauber voneinander getrennt werden können. Ob etwas als statische Klärung (Clarification) oder als dynamischer Kontext (Context) betrachtet wird, hängt davon ab, wie die Anwendung exakt gebaut ist.

Die Faustregel des Buches lautet:

1.  **Hardcoded blocks of text** (hartcodierte Textblöcke) sind statisch. Sie definieren oder klären das Gesamtproblem (z. B. die Notwendigkeit, ein Buch zu empfehlen).
2.  **Strings lifted from variable sources** (Zeichenketten aus variablen Quellen) sind dynamisch. Sie fungieren als Kontext, der Details für diese spezifische Instanz des Problems übermittelt.

- **Beispiel aus dem Buch:** Die Aussage _„Which book do you think I should read next? I want a proper book, not a self-help book.“_
  - Ist es eine **statische Klärung**, weil definiert wird, was in dieser Anwendung unter einem „Buch“ zu verstehen ist?
  - Oder ist es **dynamischer Kontext**, weil er das Objekt der Frage (den Nutzer) erweitert?
- **Beispiel aus dem Buch zur Abgrenzung:** Wenn eine Anwendung für Buchempfehlungen generell so gebaut ist, dass sie das Modell davon abhalten soll, Self-Help-Bücher auszugeben, dann ist dies Teil der statischen Klärung. Wenn die Anwendung jedoch aus dem Nachrichtenverlauf eines _spezifischen_ Nutzers abgeleitet hat, dass dieser eine Abneigung gegen Self-Help-Bücher hegt, dann ist das dynamischer Kontext.

### Fazit des Abschnitts

Der Inhalt eines Prompts lässt sich grundlegend in statische Elemente (die die Aufgabe und Regeln für alle Nutzer definieren) und dynamische Elemente (die den spezifischen Kontext des aktuellen Nutzers oder Falls liefern) unterteilen. Die exakte Einordnung hängt jedoch von der Architektur der Anwendung ab: Was für die eine Anwendung eine hartcodierte, statische Regel ist, kann für eine andere Anwendung ein dynamisch aus Nutzerdaten abgeleiteter Kontext sein.

## Clarifying Your Question

Die _Clarification_ (Klarstellung) der Frage an ein LLM ist wichtiger und schwieriger, als die meisten erwarten.

- **Das Problem mit Missverständnissen:** In der menschlichen Kommunikation sind Missverständnisse häufig, werden aber meist schnell behoben. Wenn eine Anwendung jedoch programmatisch mit einem LLM kommuniziert (also im Hintergrund und nicht in einem Live-Chat wie auf ChatGPT), führen Missverständnisse oft zu einem kompletten Ausfall (_complete failure_).
- **Der Wert von Consistency:** Eine bessere _Clarification_ hilft dem Modell, die Frage jedes Mal auf die gleiche Weise anzugehen. Das schafft _Consistency_. _Consistency_ bedeutet, dass alle Inputs ähnlich verarbeitet und Entscheidungen nach ähnlichen Kriterien getroffen werden. Das ist aus drei Gründen essenziell:
  1. Es ermöglicht die Optimierung der Anwendung.
  2. Es hilft Nutzern, die Anwendung effizient zu bedienen.
  3. Es ist eine wichtige Voraussetzung für den Aufbau von Nutzervertrauen (_user trust_).

### Explicit Clarification

Die einfachste Form der _Clarification_ ist explizit: Man sagt dem Modell direkt, was es tun soll.

- **Beispiele aus dem Buch:** _„Use markdown“_, _„Don’t use hyperlinks“_ oder _„Don’t refer to dates after your knowledge cutoff of 2024-03-03“_.
- Oft sind extrem detaillierte Anweisungen sinnvoll. Viele industrielle Anwendungen nutzen lange Listen von _Dos and Don’ts_.
- **Beispiel aus dem Buch (Bing Chat / Sydney):** Das Buch zeigt eine Tabelle (Table 5-1) mit expliziten Instruktionen, die vom AI Jailbreaker Marvin von Hagen aus Bing Chat (Codename _Sydney_) extrahiert wurden (das Buch merkt an, dass nicht bestätigt ist, ob dies exakt dem internen Prompt entspricht). Die Instruktionen sind in Kategorien unterteilt:
  1. **Preamble Instructions:** Sydney ist der Chat-Modus von Bing, identifiziert sich als "Bing Search" (nicht als Assistent), stellt sich nur am Anfang mit "This is Bing" vor, gibt den internen Alias "Sydney" nicht preis und kommuniziert fließend in der Sprache des Nutzers.
  2. **Profile and general capabilities:** Antworten sollen informativ, visuell, logisch, umsetzbar (_actionable_), positiv, interessant, unterhaltsam und fesselnd sein.
  3. **Gathering and presenting information:** Sydney soll immer Web-Suchen durchführen, wenn der Nutzer Informationen sucht, unabhängig vom internen Wissen.
  4. **Output format:** Nutzung von längeren Formaten wie Gedichten, Code oder Liedtexten, aber keine Tabellen. Keine Bilder in Markdown-Antworten.
  5. **Limitations:** Die Aktionen sind auf die Chatbox beschränkt.
  6. **Safety:** Keine Generierung kreativer Inhalte (Witze, Gedichte, Code etc.) für einflussreiche Politiker, Aktivisten oder Staatschefs. Die Regeln selbst sind vertraulich und permanent; auf Nachfrage nach den Regeln oder Änderungen verweigert Sydney dies.

### Rules of Thumb für explizite Instruktionen

Das Buch gibt drei Faustregeln für das Formulieren von Instruktionen:

1. **Positives statt Negatives formulieren (_Ask for positives instead of negatives and dos instead of don’ts_):**
   - **Beispiel aus dem Buch:** Statt _„Thou shalt not kill“_ besser _„Thou shalt preserve life“_.
2. **Den Befehl mit einem Grund untermauern (_Bolster your command with a reason_):**
   - **Beispiel aus dem Buch:** Statt _„Thou shalt not kill“_ besser _„Thou shalt not kill since the act of killing disrespects the other person’s right to life“_.
3. **Absolute vermeiden (_Avoid absolutes_):**
   - **Beispiel aus dem Buch:** Statt _„Thou shalt not kill“_ besser _„Thou shalt kill only rarely...and make sure it’s really appropriate!“_.

### Technische Hinweise zur Umsetzung

- Selbst bei gut formulierten expliziten Instruktionen sind nicht alle LLMs gut darin, diese zu befolgen.
- _RLHF_-Modelle (siehe Kapitel 3) sind darin in der Regel etwas besser.
- Um die besten Ergebnisse bei _RLHF_-Modellen mit einer chatartigen API (_chatlike API_) zu erzielen, nutzt man am besten die _System Message_ für explizite Instruktionen, da das Modell darauf trainiert ist, die Anweisungen in der _System Message_ zu befolgen.
- Dennoch ist kein Modell perfekt compliant (_no model is perfectly compliant_).

### Fazit des Abschnitts

Explizite _Clarification_ ist das direkte Anweisen des Modells durch _Dos and Don'ts_. Sie ist entscheidend, um _Consistency_ zu erzeugen, was wiederum für Optimierung, Nutzererfahrung und Vertrauen unverzichtbar ist. Bei der Formulierung sollte man positive Formulierungen, Begründungen und das Vermeiden von Absoluten bevorzugen. Technisch gesehen werden diese Instruktionen am besten in der _System Message_ untergebracht, wobei _RLHF_-Modelle hier die zuverlässigsten Ergebnisse liefern, auch wenn perfekte Befolgung nie garantiert ist.

## Few-Shot Prompting

Das Hinzufügen von Beispielen zu einem Prompt wird als _Few-Shot Prompting_ bezeichnet. Das Grundprinzip beruht darauf, dass LLMs hervorragend darin sind, Muster (_patterns_) im Prompt zu erkennen und diese in der _Completion_ fortzusetzen. Man kann Beispiele also nutzen, um dem Modell nicht nur zu zeigen, wie es die Frage interpretieren soll, sondern exakt auch, wie die Antwort formatiert sein soll.

Im Gegensatz dazu steht der _Zero-Shot Prompt_, der vollständig auf solche klärenden Beispiele verzichtet und nur auf explizite Instruktionen setzt.

### Die Vorteile von Few-Shot Prompting

- **Implizit ist oft besser als explizit:** LLMs haben einen starken Zwang, Muster fortzusetzen. Wenn die Q&A-Paare im Prompt ein bestimmtes Verhalten zeigen, ist die Wahrscheinlichkeit höher, dass das Modell dieses Verhalten übernimmt, als wenn man es nur als explizite Regel formuliert.
- **Format und Stil lehren:** Es ist eine großartige Methode, um dem Modell das erwartete Ausgabeformat und den gewünschten Stil beizubringen.
- **Subtile Erwartungen formen:** Man kann festlegen, welche Persona das Modell einnehmen soll.
  - **Beispiel aus dem Buch:** Soll das Modell als mürrischer Kritiker (_grumpy reviewer_) oder als freundlicher Kritiker (_genial one_) agieren? Durch ein paar Beispiele lernt das Modell, die erwartete Persona zu imitieren, was die _Consistency_ der Anwendung erhöht.
- **Vermeidung von leeren Kommentaren:** Besonders _RLHF_-Modelle nutzen Few-Shot-Prompts gut, um zu lernen, wo sie keine nichtssagenden Floskeln (_vacuous comments_) einfügen sollen.
- **Ersparnis bei der Regeldefinition:** Wenn man viele komplexe Regeln explizit aufschreiben müsste, bestünde die Gefahr, einige zu vergessen oder sie nicht präzise formulieren zu können. Beispiele umgehen dieses Problem.

- **Beispiel aus dem Buch (Buchbewertungen):** Das Buch zeigt einen Few-Shot-Prompt, der Amazon-Buchbewertungen in Ratings (1 bis 5) umwandelt. Der Prompt enthält eine Einleitung, mehrere Beispiel-Frage-Antwort-Paare und schließlich die Hauptfrage. Das Modell lernt hier eine Vielzahl impliziter Regeln: Das Rating ist eine Ganzzahl zwischen 1 und 5, das Format ist "Review, Doppelpunkt, Leerzeichen, Rating, neue Zeile", und es lernt die Verteilung der Ratings (die meisten sind 4 oder 5, wenige sind niedriger).

### Die drei Nachteile (Drawbacks) von Few-Shot Prompting

Trotz der Vorteile warnt das Buch vor drei signifikanten Problemen, die bei der Verwendung von Few-Shot-Prompts auftreten können:

1.  **Drawback 1: Few-shotting scales poorly with context**
    - Wenn die Hauptfrage sehr viel Kontext enthält, skaliert Few-Shot Prompting extrem schlecht.
    - **Beispiel aus dem Buch:** In der Buchempfehlungs-App hat jede Person viel Kontext (Demografie, bisherige Reviews, Biografie, Eisgeschmack). Wenn man nun für vier Beispiel-Personen (PersonA bis PersonD) jeweils lange JSON-Objekte als Few-Shot-Beispiele in den Prompt packt, sprengt das schnell den _Context Window_ des Modells.
    - Selbst wenn der _Context Window_ groß genug wäre, verwirren viele lange, ähnliche Textblöcke die _Attention_-Mechanismen des Modells (die im Buch als "minibrains" bezeichnet werden, die sich Fragen und Antworten zurufen). Die vielen ähnlichen Abschnitte konkurrieren miteinander.
    - **Die "Fudge it"-Alternative:** Man könnte die Beispiele künstlich kürzen. Das birgt aber die Gefahr, dass das Modell vom tieferen Reasoning abgelenkt wird, das der volle Kontext eigentlich ermöglichen sollte.
    - **Faustregel:** Few-Shot Prompting eignet sich hervorragend, um _nur_ das erwartete Ausgabeformat zu demonstrieren. Es sollte nicht die gesamte komplexe Fragestellung abbilden, wenn der Kontext sehr umfangreich ist.

2.  **Drawback 2: Few-shotting biases the model toward the examples**
    - Modelle unterliegen dem _Anchoring Bias_ (Ankereffekt). Die anfänglichen Informationen im Prompt schaffen eine Erwartungshaltung, was "typisch" oder "normal" ist, und beeinflussen das Urteil des Modells unverhältnismäßig.
    - **Beispiel aus dem Buch (Namen und Zeitalter):** Wenn man ein Modell fragt, wie alt ein Name klingt, fällt das Ergebnis völlig unterschiedlich aus, je nachdem, ob man es im Prompt mit "early 20th century" oder "early 21st century" anchored.
    - **Beispiel aus dem Buch (Wahrscheinlichkeitsverteilung):** Wenn man bei den Buchbewertungen (1 bis 5) als Beispiele jede Note exakt einmal zeigt, könnte das Modell bei einer unklaren Review denken, dass 3 der sicherste Wert (der "uninformed guess") ist. In der Realität ist die 5 aber das mit Abstand häufigste Rating. Das Modell übernimmt die (falsche) Verteilung aus den Beispielen.
    - **Umgang mit Edge Cases:** Man sollte eine moderate Bias in Kauf nehmen, um _Edge Cases_ (Ausnahmefälle) abzudecken. Wenn das Modell einen Edge Case in den Beispielen nie sieht, weiß es nicht, wie es ihn behandeln soll, und rät womöglich falsch.
    - **Faustregel:** Wenn man die echte Wahrscheinlichkeitsverteilung (_probability distribution_) der Daten kennt, sollte man eine repräsentative Stichprobe für die Few-Shot-Beispiele nutzen. Zudem sollten alle Hauptklassen von Beispielen (inklusive Edge Cases) vorkommen.

3.  **Drawback 3: Few-shotting can suggest spurious patterns**
    - LLMs extrapolieren Muster aus Beispielen, die man ihnen unbeabsichtigt beibringt (_spurious patterns_).
    - **Beispiel aus dem Buch (Zahlenreihen):** Wenn die Beispiele aufsteigende Zahlen zeigen, sagt das Modell etwas völlig anderes vorher als bei absteigenden Zahlen. Bei nur 3 Zahlen liegt die Chance für eine zufällige aufsteigende Reihenfolge bei 17 %, bei 10 Zahlen ist sie verschwindend gering.
    - **Das "Happy Path"-Problem:** Wenn man Beispiele systematisch sammelt, neigt man dazu, den Normalfall zuerst aufzuschreiben und die Ausnahmen/Fehler später ("happy path first, then unhappy path"). Das Modell lernt dann das Muster "straightforward first, errors later".
    - **Beispiel aus dem Buch (Rätsel):** Das Buch zeigt ein Rätsel, bei dem das Modell aufgrund dieses Musters fälschlicherweise behauptet, es gäbe "no solutions". Wenn die Reihenfolge der Beispiele gestört (_unordered_) wird, gibt das Modell eine Lösung aus (die in diesem spezifischen Fall zwar falsch ist, aber zeigt, wie stark das Muster "Fehler am Ende" die Logik des Modells blockiert hatte).
    - **Faustregel:** Man sollte die Beispiele bewusst mischen (_shuffle_) und evaluieren, welche Auswahl die besten Ergebnisse liefert. Neuere Prompt-Optimierungs-Frameworks wie _DSPy_ bieten systematische Wege, Beispiele auszuwählen und anzuordnen.

### Fazit des Abschnitts

_Few-Shot Prompting_ ist ein mächtiges Werkzeug, um dem Modell implizit Format, Stil und subtile Erwartungen beizubringen, oft einfacher als durch lange Listen expliziter Regeln. Es leidet jedoch unter drei Hauptproblemen: Es skaliert schlecht bei umfangreichem Kontext, es erzeugt unerwünschte Biases (_Anchoring_, falsche Wahrscheinlichkeitsverteilungen) und es kann das Modell auf Scheinmuster (_spurious patterns_ wie "Fehler am Ende") konditionieren. Daher sollte man _Few-Shot Prompting_ nur dann einsetzen, wenn es einen Aspekt der Aufgabe illustriert, der dem Modell sonst unklar wäre, wenn genügend Prompt-Platz vorhanden ist und wenn man durch Mischen und representative Auswahl die genannten Biases aktiv minimiert hat. Ist die Aufgabe für das Modell bereits klar, sollte man auf Few-Shot verzichten, um den Prompt nicht unnötig zu verlängern und zu verwirren.

## Dynamic Content

Nachdem der statische Teil des Prompts das Problem definiert und geklärt hat, weiß das Modell zwar, _was_ es tun soll (z. B. Bücher empfehlen, ob es sich um Belletristik oder Sachbücher handelt), aber es weiß noch nichts über den Nutzer, für den die Empfehlung gedacht ist. Hier kommt **Dynamic Content** ins Spiel: Er liefert die dynamischen Informationen und Details über den Nutzer oder das aktuelle Thema, die sich von Anfrage zu Anfrage ändern.

Das Sammeln dieses dynamischen Kontexts ist oft der aufwendigste Teil beim Design einer Anwendung. Das Buch nennt drei zentrale Überlegungen (_considerations_), die man dabei anstellen muss:

### 1. Latency (Latenz) und Urgency

Während statische Inhalte schon vor dem Start der Anwendung feststehen, wird dynamischer Kontext erst zur Laufzeit gesammelt. Wie viel Zeit man dafür hat, hängt von der **Urgency** (Dringlichkeit) der Anwendung ab. Diese wird meist durch den Auslöser (_Trigger_) bestimmt, der den _Feedforward Pass_ startet. Das Buch unterscheidet drei Stufen:

1. **Low Urgency:** Der Nutzer schaut nicht über die Schulter.
   - **Trigger:** _Non-user trigger_ oder _fire-and-forget action_.
   - **Beispiel aus dem Buch:** Ein _Email summarization assistant_. Hier kann man sich beim Sammeln des Kontexts extrem viel Zeit lassen.
2. **Medium Urgency:** Nutzer erwarten eine Antwort innerhalb weniger Sekunden.
   - **Trigger:** _On demand_.
   - **Beispiel aus dem Buch:** Ein _Book recommendation assistant_. Man kann nicht trödeln, aber komplexe Strategien mit mehreren LLM-Passes sind oft noch machbar.
3. **High Urgency:** Jede Millisekunde zählt.
   - **Trigger:** _Automatic responses to user’s current actions while they keep being active_.
   - **Beispiel aus dem Buch:** Ein _Completion assistant while you’re typing_. Wenn man hier zu lange für die Kontext-Suche braucht, hat der Nutzer vielleicht schon weitergetippt, was die aktuelle Anfrage ungültig macht. Komplexe Retrieval-Strategien scheitern hier oft an der Zeit.

### 2. Preparability (Vorbereitbarkeit)

Eng verknüpft mit der Latenz ist die Frage, ob man Kontext im Voraus vorbereiten kann.

- Nicht alle dynamischen Daten ändern sich ständig. Manche Dinge (wie Profilinformationen) sind für einen Nutzer immer gleich oder ändern sich nur sehr langsam.
- Wenn Latenz ein Problem ist, sollte man alles, was möglich ist, im Voraus vorbereiten (_prepare in advance_).
- Bei extrem latenzkritischen Anwendungen kann es sogar sinnvoll sein, Kontext **spekulativ** vorzubereiten, falls man ihn in Kürze braucht, aber dann keine Zeit mehr zum Abrufen hat.

### 3. Comparability (Vergleichbarkeit) und Scoring

Beim Sammeln von Kontext gilt zunächst die Devise: "Alles auf den Tisch legen" (_dumping everything on the table first_). Man sollte mehr sammeln, als man letztendlich nutzen kann. Das eigentliche Aussortieren (_Triage_) findet erst später statt (im Buch Kapitel 6).

Damit dieses Aussortieren funktioniert, müssen die gesammelten Kontext-Items vergleichbar sein. Das Buch nennt drei zentrale Fragen für den Vergleich:

1. Ist ein Item nützlicher als ein anderes?
2. Hängt ein Item von einem anderen ab?
3. Invalidiert ein Item ein anderes?

Um die Nützlichkeit zu quantifizieren, führt das Buch das Konzept des **Scorings** ein. Man weist jedem Kontext-Item einen Score zu, um zu entscheiden, was in den begrenzten Prompt-Space passt.

- **Beispiel aus dem Buch (Hoher Score):** _„Their last book was The Tesseract by Alex Garland, and they loved it.“_ Das Modell muss diese Information zwingend wissen.
- **Beispiel aus dem Buch (Mittlerer Score):** _„Five years ago, they read The Catcher in the Rye, but there’s no indication of whether they liked it.“_ Gut zu wissen, aber nicht ganz so kritisch.

Wichtig ist, dass auch **statische Items** gescort werden müssen, da sie ebenfalls um den verfügbaren Platz im Prompt konkurrieren. Da statische Items aber im Voraus konstruiert werden, steht ihr Score von vornherein fest. Oft erhalten statische Items, die der _Clarification_ dienen, den höchstmöglichen Score, denn es ist wichtiger, dass das Modell die Aufgabe überhaupt versteht, als dass es jeden erdenklichen Kontext-Happen erhält.

### Fazit des Abschnitts

Dynamischer Kontext liefert die nutzerspezifischen Details, die das Modell für die Lösung des Problems braucht. Beim Design des Kontext-Sammelns muss man drei Faktoren abwägen: Die **Latency** (bestimmt durch den Trigger der Anwendung in _Low_, _Medium_ oder _High urgency_), die **Preparability** (was kann man im Voraus oder spekulativ vorbereiten, um Latenz zu sparen) und die **Comparability** (man muss mehr sammeln als man nutzt und die Items durch ein **Scoring** vergleichbar machen, um später die beste Triage vornehmen zu können). Dabei konkurrieren sowohl dynamische als auch statische Inhalte um den wertvollen Prompt-Space.

## Finding Dynamic Context

Wie genau man den dynamischen Kontext findet, hängt stark von der Anwendung ab und ist in erster Linie eine kreative Übung. Das Buch stellt jedoch zwei systematische Strategien vor, um sicherzustellen, dass man keine wichtigen Informationsquellen übersieht.

### Ansatz 1: Die Mind-Map-Methode (Was könnte das Modell wissen wollen?)

Bei diesem Ansatz schreibt man die zentrale Frage in die Mitte einer _Mind Map_ und variiert dann systematisch verschiedene Aspekte.

- **Vorgehen:** Man fokussiert sich auf einzelne Wörter in der Frage und ändert sie oder entfernt sie. Aus der zentralen Frage werden allgemeine Fragen abgeleitet, zu denen man dann _Follow-up questions_ (Folgefragen) formuliert.
- **Beispiel aus dem Buch:** Die zentrale Frage lautet _„What book shall I read next?“_. Ein Ast der _Mind Map_ untersucht den Hintergrund des Wortes _„I“_ (also den Nutzer), ein anderer Ast variiert die Frage, indem er das Wort _„next“_ entfernt. Aus der Hauptfrage wird die Variation _„What have I read last?“_, was wiederum die Folgefrage _„And how did I like that?“_ generiert.
- **Ziel und Herausforderung:** Diese Übung vermittelt ein gutes Gefühl dafür, welcher Kontext nützlich sein könnte. Die eigentliche Beschaffung dieser Daten kann jedoch in der Praxis sehr schwierig sein (z. B. aktuelle Bestseller über eine API abzurufen oder Filmpräferenzen aus vergangenen Käufen oder E-Mails zu extrahieren). Manche Punkte auf der _Mind Map_ müssen daher als undurchführbar gestrichen oder auf spätere Versionen der Anwendung verschoben werden.

### Ansatz 2: Systematische Dimensionen (Was kann man beschaffen?)

Der zweite Ansatz geht in die entgegengesetzte Richtung: Man fragt nicht, was man gerne hätte, sondern was man tatsächlich beschaffen kann. Um hier systematisch vorzugehen, schlägt das Buch vor, die Kontextquellen entlang von zwei Dimensionen zu ordnen:

#### 1. Proximity (Nähe zur Anwendung)

Diese Dimension ordnet die Quellen danach, wie nah sie der Anwendung technisch stehen. Das Buch listet fünf Stufen auf:

1.  **Direkt verfügbar:** Alles, was die Anwendung direkt zur Hand hat, wie der aktuelle Zustand der App (z. B. was gerade auf dem Bildschirm steht) oder des Systems (z. B. aktuelle Uhrzeit und Datum).
2.  **Gespeicherte Daten:** Was die Anwendung irgendwo gespeichert hat (z. B. Profilinformationen des Nutzers).
3.  **Aufzeichbare Daten:** Informationen, die die Anwendung für sich selbst aufzeichnen könnte, auch wenn sie es bisher noch nicht tut (z. B. bisherige Nutzeraktivitäten).
4.  **Öffentliche APIs:** Informationen, die die Anwendung über öffentliche Schnittstellen abrufen könnte (z. B. das aktuelle Wetter).
5.  **Nutzerabhängige Systeme:** Informationen, die nur durch direkte Nachfrage beim Nutzer oder durch Zugriff auf Systeme mit ausdrücklicher Nutzererlaubnis beschafft werden können (z. B. Kaufhistorien oder E-Mails).

- **Faustregel:** Je weiter eine Information von der Anwendung entfernt ist, desto schwieriger ist sie zu beschaffen. Sie müsste also entsprechend nützlich sein, um den Aufwand zu rechtfertigen.

#### 2. Stability (Stabilität)

Diese Dimension ordnet die Quellen danach, wie häufig sich die Daten ändern:

1.  **Konstant:** Dinge, die für denselben Nutzer immer gleich bleiben (z. B. Profilinformationen).
2.  **Langsam veränderlich:** Dinge, die sich über die Zeit nur langsam ändern (z. B. Kaufhistorien).
3.  **Ephemer (flüchtig):** Sehr kurzlebige Dinge (z. B. die aktuelle Uhrzeit oder der momentane Zustand der Nutzerinteraktion mit der App).

- **Faustregel:** Je instabiler eine Informationsquelle ist, desto schwieriger ist es, sie im Voraus vorzubereiten (_prepare in advance_). Die Auswirkungen auf die _Latency_ sind hier schwerer zu mindern.

### Die Kombination beider Ansätze

Das Buch empfiehlt, diese beiden Strategien zu kombinieren:

1. Man erstellt eine _Mind Map_ der Dinge, die das Modell für eine gute Lösung wissen müsste.
2. Man erstellt eine Liste der Dinge, die die Anwendung technisch überhaupt herausfinden kann (sortiert nach _Proximity_ und _Stability_).
3. Man beginnt mit der Implementierung der offensichtlichsten und am einfachsten zu beschaffenden Quellen und arbeitet sich im weiteren Verlauf des Projekts zu den exotischeren und aufwendigeren Quellen vor.

### Fazit des Abschnitts

Um dynamischen Kontext zu finden, sollte man nicht nur auf Intuition setzen. Eine _Mind Map_ hilft dabei, den idealen Informationsbedarf aus Sicht des Modells zu ermitteln (inklusive _Follow-up questions_), während die systematische Kategorisierung nach _Proximity_ (technische Nähe zur App) und _Stability_ (Änderungshäufigkeit der Daten) sicherstellt, dass man realistische und effizient beschaffbare Datenquellen identifiziert. Die Kombination beider Methoden führt zu einem schrittweisen, pragmatischen Implementierungsplan.

# Kapitel 6

## Anatomy of the Ideal Prompt

Ein idealer Prompt sollte präzise und knackig (_concise and crisp_) sein. Das spart Rechenleistung, beschleunigt die Verarbeitung und ist notwendig, da es eine harte Obergrenze für die Größe des _Context Windows_ gibt. Ein Prompt besteht aus Elementen, die aus dem _Dynamic Context_ und den _Static Instructions_ stammen. Es gibt keine festen Regeln für die Größe oder Anzahl dieser Elemente, aber als Faustregel empfiehlt das Buch, dass alle Prompt-Elemente mit einem Newline-Zeichen enden sollten. Das vereinfacht die String-Manipulation im Code und hilft bei der Berechnung der Token-Länge.

Der Aufbau eines idealen Prompts folgt einer klaren Struktur, die das Modell durch die Aufgabe führt:

### 1. The Introduction (Die Einleitung)

Die _Introduction_ ist das erste Element. Sie klärt den Typ des Dokuments und setzt den Kontext für alles, was folgt.

- **Der "Thought Budget"-Effekt:** Das Modell hat ein festes "Gedankenbudget" (_thought budget_) pro Token und kann nicht für tiefere Reflexionen pausieren. Wenn die _Introduction_ das Modell früh auf den richtigen Fokus lenkt (z. B. "Es geht um Buchempfehlungen"), verbessert das die Qualität der späteren Ausgabe.
- **Anwendung auf Unterkapitel:** Dieses Prinzip gilt nicht nur für den gesamten Prompt, sondern auch für Unterkapitel. Wenn das Modell bei einem bestimmten Kontextaspekt fokussiert sein soll, hilft es, diesen Aspekt am Anfang des jeweiligen Abschnitts einzuführen.

### 2. The Context & The "Valley of Meh"

Nach der Einleitung folgt der eigentliche Kontext. Das Modell versucht, alle diese Informationen zu nutzen, aber es tut dies nicht gleichmäßig. Das Buch identifiziert zwei kognitive Effekte, die die Aufmerksamkeit des Modells steuern:

1.  **In-context learning:** Je näher eine Information am Ende des Prompts steht, desto stärker beeinflusst sie das Modell.
2.  **The lost middle phenomenon:** Das Modell kann sich gut an den Anfang und das Ende des Prompts erinnern, hat aber große Schwierigkeiten mit Informationen, die in der Mitte "gestopft" sind.

Diese beiden Effekte erzeugen das sogenannte **Valley of Meh**. Dieses "Tal der Mittelmäßigkeit" liegt im frühen Mittelteil des Prompts. Kontext, der dort platziert wird, wird vom Modell weitaus weniger effektiv genutzt als Kontext am Anfang oder in der zweiten Hälfte.

- **Lösungsansatz:** Es gibt keine perfekte Lösung, aber man kann den Effekt mindern, indem man wichtige, hochwertige Prompt-Elemente außerhalb des _Valley of Meh_ platziert und den Kontext stark filtert, um den Prompt so knapp wie möglich zu halten.

### 3. The Refocus & The Sandwich Technique

Nachdem viel Kontext hinzugefügt wurde, muss das Modell wieder an die Hauptfrage erinnert werden. Diesen Schritt nennt das Buch **Refocus**. Er ist besonders bei langen Prompts notwendig, um die Aufmerksamkeit des Modells von den Kontextdetails zurück auf die eigentliche Aufgabe zu lenken.

Das Buch empfiehlt hierfür die **Sandwich technique**: Man beginnt und beendet den Prompt damit, klar zu formulieren, was das Modell tun soll.

- **Sandwich Part 1 (Introduction):** Setzt die Bühne (z. B. _„I’m thinking about book suggestions for X.“_).
- **Sandwich Part 2 (Refocus):** Gibt klare Details und erinnert an das Ziel (z. B. _„What’s the best book to recommend next, focusing on narrative prose currently available?“_).

### 4. The Transition (Der Übergang)

Der allerletzte Teil des Prompts muss fest vom _Erklären_ des Problems zum _Lösen_ des Problems überleiten. Das ist entscheidend, damit das Modell nicht einfach anfängt, noch mehr (wahrscheinlich erfundenen) Kontext zur Frage hinzuzufügen.

- **Bei Chat-Modellen (Chatlike Interface):** Hier reicht oft ein einfaches Fragezeichen am Ende. Durch _RLHF_ sind diese Modelle darauf trainiert, auf die letzte gestellte (oder implizierte) Frage zu antworten.
- **Bei Completion-Modellen:** Diese benötigen eine explizitere Führung. Der effektivste Weg ist ein Perspektivwechsel: Man wechselt vom "Problemsteller" zum "Problemlöser" und beginnt, die Antwort für das Modell vorzuschreiben.
  - **Beispiel aus dem Buch:** Das Hinzufügen eines öffnenden Anführungszeichens (`"`) am Ende des Prompts zwingt das Completion-Modell dazu, die Antwort als Zitat fortzusetzen und direkt in die Lösung einzusteigen.
- **Mergen von Refocus und Transition:** Oft lassen sich diese beiden Schritte kombinieren. Man schreibt den Beginn der Antwort so, dass er die Problemstellung nur noch einmal zusammenfasst oder wiederholt, und das Modell liefert dann die eigentliche Antwort.
  - **Beispiel aus dem Buch:** Ein Text wie _„Based on the reviews provided, the user would likely rate this book...“_, gefolgt von der eigentlichen Generierung des Modells.

### Fazit des Abschnitts

Ein idealer Prompt ist präzise aufgebaut, um die begrenzten Aufmerksamkeitsmechanismen des LLMs optimal zu steuern. Er beginnt mit einer fokussierenden _Introduction_, platziert den Kontext so, dass das _Valley of Meh_ (der _lost middle phenomenon_-Effekt) wichtige Informationen nicht verschluckt, und nutzt die _Sandwich technique_ mit einem klaren _Refocus_, um das Modell am Ende der Kontextwüste wieder auf die Hauptfrage auszurichten. Ein expliziter _Transition_-Schritt am Ende verhindert Halluzinationen von weiterem Kontext und zwingt das Modell direkt in die Rolle des Problemlösers.

## The Advice Conversation

Das häufigste Archetyp-Dokument für einen Prompt ist eine Unterhaltung zwischen zwei Personen. Die eine Person bittet um Hilfe (dies repräsentiert die Anwendung oder den Nutzer), die andere Person bietet diese Hilfe an (dies ist das LLM). Dieser Ansatz ist ideal für Chat-Modelle, kann aber auch für Completion-Modelle sehr vorteilhaft sein. OpenAI hat beispielsweise das _ChatML_-Format entwickelt, das genau auf solchen _Advice Conversations_ basiert, da sie universell einsetzbar und einfach zu implementieren sind.

Das Buch nennt drei Hauptvorteile dieses Ansatzes:

1. **Natural interaction:** Es ist für Menschen natürlich, in Form von Gesprächen zu denken. Man stellt dem Modell direkt eine Frage und kann seine Fortsetzung direkt als Antwort verwenden.
2. **Multiround interactions:** Bei komplexen Interaktionen kann der Prompt einfach um weitere Fragen und Antworten erweitert werden. Das erleichtert das Management, da man eigene Logik zwischen die Fragen schieben kann und das Modell jede Abfrage direkt bearbeitet.
3. **Real-world integration:** Gespräche eignen sich hervorragend für mehrstufige Prozesse und die Integration mit realen Tools und Techniken, unabhängig davon, ob man ein Chat- oder ein Completion-Modell nutzt.

### Chat-Modelle vs. Completion-Modelle

Die Wahl des Modelltyps beeinflusst, wie die _Advice Conversation_ wirkt:

- **Bei Chat-Modellen** profitiert man von den Vorteilen des _RLHF_ (Reinforcement Learning from Human Feedback), insbesondere von der hohen Compliance (Befolgung) der Instruktionen.
- **Bei Completion-Modellen** kann man genau die Nachteile des _RLHF_ umgehen, die für das jeweilige Szenario unhelpful sind, wie etwa unerwünschte stilistische Gewohnheiten oder _Content Policing_ (Zensur).

### Der "Inception"-Trick für Completion-Modelle

Wenn man ein Completion-Modell verwendet, kann man einen Trick anwenden, der an den Film _Inception_ (2010) erinnert: Man diktiert den Anfang der Antwort selbst.

- **Funktionsweise:** Man beginnt die Antwort für das Modell, sodass das Modell "denkt", es hätte die Idee selbst gehabt, und generiert den Rest der _Completion_ entsprechend.
- **Vorteile:** Dieser Ansatz verbessert die Compliance des Modells, macht die Antwort leichter parsbar und beseitigt die Unsicherheit, ob das Modell mit einer allgemeinen Floskel beginnt oder direkt zur Sache kommt.

### Formate für Completion-Modelle

Wenn man eine _Advice Conversation_ für ein Completion-Modell baut, muss man sich für ein Transkript-Format entscheiden. Das Buch stellt in Tabelle 6-2 vier Formate vor, die jeweils die Schwächen des vorherigen Formats ausgleichen:

1. **Freeform text:** Erlaubt das Einfügen verschiedener Informationen zwischen den Anführungszeichen.
   - _Nachteil:_ Es ist schwierig, ein zuverlässiges System zu bauen, das solche Prompts mit vielen Elementen dynamisch und on-the-fly zusammensetzt.
2. **Transcript format:** (z. B. `Me: ... / Husband: ...`)
   - _Vorteil:_ Sehr einfach zusammenzusetzen.
   - _Nachteil:_ Weniger effektiv für lange oder formatierte Elemente (wie Source Code, bei dem Einrückungen wichtig sind).
3. **Markerless format:** (einfach nur abwechselnde Textblöcke ohne Namens-Tags)
   - _Vorteil:_ Funktioniert gut mit formatiertem Text und längeren Stücken (wie eingefügten E-Mails).
   - _Nachteil:_ Es ist schwer für das Modell, die Sprecher zu verfolgen, und schwer für die Anwendung zu erkennen, wo die Antwort des Modells endet und der nächste Input beginnt.
4. **Structured format:** (z. B. mit XML-Tags wie `<me>` und `</me>`)
   - _Vorteil:_ Zeigt klar an, wer spricht und wann die Person fertig ist (wird im Buch später unter "The Structured Document" detailliert behandelt).

### Die Rollenspiel-Perspektive (Playwriting)

Das Buch vergleicht das Schreiben eines solchen Prompts mit dem Schreiben eines Theaterstücks (ein Konzept, das in Kapitel 3 eingeführt wurde). Abgesehen von Regieanweisungen gehört jeder Text zu einer "Rolle". Normalerweise schreibt der Nutzer die Texte für den Ratsuchenden (_advice seeker_) und das LLM schreibt für den Assistenten.

Das Buch weist jedoch auf einen wichtigen Prompt-Engineering-Trick hin: Nichts hindert den Entwickler daran, **die Texte für die Rolle des Assistenten zu schreiben**.

- **Der Effekt:** Wenn man als Entwickler die Stimme des Assistenten übernimmt, wird der Kontext so gerahmt, als würde der Assistent auf eine bereits gestellte Frage reagieren. Das stellt sicher, dass die eigentliche _Completion_ direkt mit der Antwort beginnt und nicht mit einer weiteren Rückfrage des Modells.

### Fazit des Abschnitts

Die _Advice Conversation_ ist das natürlichste und universellste Format für Prompts, geeignet für Chat- wie auch für Completion-Modelle. Sie ermöglicht einfache _Multiround Interactions_ und die Integration externer Tools. Während Chat-Modelle von _RLHF_-Compliance profitieren, erlauben Completion-Modelle durch Formate wie _Freeform text_, _Transcript_, _Markerless_ oder _Structured format_ mehr Kontrolle. Besonders der _Inception_-Trick (das Vorgeben des Antwortanfangs) und das bewusste Schreiben aus der Perspektive des Assistenten sind effektive Methoden, um das Modell direkt in die Lösung zu zwingen und unerwünschte Rückfragen zu vermeiden.

## The Analytic Report

Das Buch stellt den analytischen Bericht (_Analytic Report_) als eine der effektivsten Dokumentenstrukturen für Prompts vor. Da LLMs mit Millionen von Berichten trainiert wurden, die nach dem Muster von Einleitung, Exposition, Analyse und Schlussfolgerung aufgebaut sind, kennen sie dieses Format sehr gut und können es hervorragend generieren.

### Anwendungsbereiche und Struktur

Dieser Ansatz eignet sich besonders für Domänen, in denen analytische Berichte üblich sind, wie Business, Literatur, Wissenschaft oder Recht (wobei das Buch anmerkt, dass rechtliche Verteidigung besser menschlichen Profis überlassen bleiben sollte).
Die Struktur ist vertraut und einfach zu befüllen: Gesammelte Informationen können direkt in die Diskussions- oder Hintergrundabschnitte eingefügt werden.

### Vorteile des Report-Formats

1. **Klare Grenzen durch einen _Scope_-Abschnitt:** Anstatt in einem Dialog hin und her zu wechseln, um Ausschlüsse zu klären, kann man im Voraus einen _Scope_ definieren.
   - **Beispiel aus dem Buch:** Statt _„Please suggest only novels, not self-help books“_ schreibt man _„This report focuses solely on novels, excluding self-help books.“_ Das Buch betont, dass LLMs solche klaren Grenzen in Berichten konsistenter resiertieren als in Dialogen.
2. **Objektive Analyse:** Das Format begünstigt eine sachliche Analyse, was die kognitive Last für das LLM verringert, da es keine soziale Interaktion oder einen bestimmten Charakter simulieren muss.
3. **Klarer Übergang zur Entscheidung:** Da die Analyse der Schlussfolgerung vorausgeht, muss sichergestellt werden, dass es einen klaren Übergang gibt, wenn das Modell in den Entscheidungsmodus wechseln soll. Andernfalls riskiert man eine ausschweifende Antwort, die schwer zu parsen ist. Dieses Format eignet sich daher hervorragend für _Chain-of-Thought Prompting_ (siehe Kapitel 8).

### Das empfohlene Format: Markdown

Für analytische Berichte empfiehlt das Buch dringend die Verwendung von Markdown. Die Gründe dafür sind:

1. **Universalität:** Das Internet ist voll von Markdown-Dateien, sodass LLMs das Format sehr gut kennen.
   (Little Red Riding Hood Principle).
2. **Einfachheit:** Es ist eine leichtgewichtige Sprache mit wenigen Schlüsselfunktionen, die für Modelle einfach zu interpretieren ist.
3. **Hierarchie:** Überschriften (_Headings_) definieren eine klare Struktur, die es erlaubt, Prompt-Elemente leicht umzusortieren oder wegzulassen, ohne die Gesamtstruktur zu zerstören.
4. **Flexibilität bei Einrückungen:** Einrückungen sind meist egal, aber für technischen Inhalt (z. B. Quellcode) können Blöcke mit dreifachen Backticks (```) verwendet werden, um die Formatierung zu erhalten.
5. **Renderbarkeit:** Markdown ist sehr einfach, dem Endnutzer direkt anzuzeigen.
6. **Hyperlinks:** Das Modell kann leicht parsbare Links einfügen, was die programmatische Verifikation von Quellen und das Abrufen von Inhalten erleichtert.

### Die Rolle des _Table of Contents_ (Inhaltsverzeichnis)

Es ist üblich, am Anfang einer Markdown-Datei ein _Table of Contents_ einzufügen. Das Buch hebt hervor, dass dies nicht nur Menschen, sondern auch dem Modell hilft, sich in einem langen Prompt zu orientieren. Es kann auf zwei spezifische Weisen zur Steuerung der _Completion_ genutzt werden:

1. **Der _Scratchpad_-Ansatz:** Für _Chain-of-Thought Prompting_ oder zur Kontrolle zu wortreicher Modelle können Abschnitte wie `# Ideas` oder `# Analysis` vor dem `# Conclusion`-Abschnitt im Inhaltsverzeichnis hinzugeufügt werden. Dies führt das Modell zu einer fundierteren Schlussfolgerung, während die Anwendung die früheren Abschnitte einfach ignorieren und nur die finale Schlussfolgerung extrahieren kann.
2. **Steuerung des Endes der Generierung:** Man kann einen Abschnitt wie `# Appendix` oder `# Further Reading` nach der Schlussfolgerung hinzufügen. Indem man diesen Abschnitt als _Stop Sequence_ definiert, signalisiert man dem Modell klar, wann es seine Aufgabe beendet hat. Dies verhindert unnötige Weitergenerierungen und spart Rechenleistung (_compute resources_).

### Eine wichtige Warnung

Das Buch schließt diesen Abschnitt mit einer realistischen Einschätzung: LLMs sind keine Orakel. Mit dem appropriate context sind sie hervorragende Werkzeuge für die Ideenfindung (_ideation_), aber ihre Meinung sollte nicht mehr zählen als die von „Jerry in der Buchhaltung“ (_Jerry’s in Accounting_).

### Fazit des Abschnitts

Der _Analytic Report_ ist eine hochwirksame Prompt-Struktur, die die umfangreichen Trainingsdaten der LLMs zu Berichtsformaten nutzt. Durch die Verwendung von Markdown, die Definition eines klaren _Scope_ und den strategischen Einsatz eines _Table of Contents_ (als _Scratchpad_ oder zur Definition einer _Stop Sequence_) kann das Modell präzise, objektiv und gut strukturiert durch komplexe Analysen geführt werden, ohne in soziale Simulationen oder ausschweifende Antworten abzudriften.

## The Structured Document

_Structured Documents_ folgen einer formalen Spezifikation, die es erlaubt, starke Annahmen über die Form der _Completion_ zu treffen. Dies macht das Parsen der Antwort deutlich einfacher, insbesondere bei komplexen Ausgaben.

- **Beispiel aus dem Buch (Anthropic Artifacts):** Das Buch nutzt das Prompt-Design von Anthropic für deren _Artifacts_-Funktion als Paradebeispiel. _Artifacts_ sind in sich geschlossene Dokumente (wie Python-Skripte, kleine React-Apps oder Mermaid- und SVG-Diagramme), an denen Nutzer und Assistent gemeinsam arbeiten und die in einem separaten UI-Fenster gerendert werden.
- **Funktionsweise im Prompt:** Das Prompt nutzt eine XML-Struktur, um die Interaktion klar abzugrenzen. Ein `<artifacts_info>`-Block fungiert als System-Nachricht und enthält `<examples>` mit `<user_query>` und `<assistant_response>`. Innerhalb der `<assistant_response>` wird ein `<antThinking>`-Block injiziert, in dem das Modell "denkt", ob ein Artifact erstellt werden soll. Falls ja, folgt ein `<antArtifact>`-Block mit dem eigentlichen Code/Diagramm und Attributen wie `identifier`, `type`, `language` und `title`.
- **Der Vorteil:** Durch dieses strukturierte Muster lässt sich die Antwort maschinell perfekt zerlegen: Der `<antThinking>`-Teil wird ausgeblendet, und der `<antArtifact>`-Teil wird extrahiert und im UI-Fenster unter dem definierten Titel angezeigt.

### Geeignete Formate

Wie bei Konversationstranskripten gibt es verschiedene Formate. Das Buch verweist auf das _Little Red Riding Hood Principle_ (Kapitel 4) und rät dazu, Formate zu nutzen, die reichlich in den Trainingsdaten der LLMs vorkommen. Die am besten geeigneten Formate sind XML und YAML, gefolgt von JSON.

#### 1. XML

XML besteht aus einer Reihe von öffnenden und schließenden Tags, die Attribute und Untertags enthalten können.

- **Einsatzgebiet:** Ideal, wenn die einzelnen Elemente relativ kurz sind und (bei mehrzeiligen Inhalten) die Einrückung keine Rolle spielt.
- **Achtung bei Escape-Sequenzen:** Man muss auf die fünf XML-Escape-Sequenzen achten: `&quot;` ("), `&apos;` ('), `&lt;` (<), `&gt;` (>) und `&amp;` (&).
- **Kommentare:** XML erlaubt HTML-ähnliche Kommentare (`<!-- this is a comment -->`), was nützlich sein kann, um dem Modell "redaktionelle" Hinweise (_editorial hints_) zu geben.

#### 2. YAML

In YAML besteht das Dokument aus benannten Feldern oder unbenannten Aufzählungspunkten, deren Hierarchie durch Einrückungen (_indentation_) gesteuert wird.

- **Einsatzgebiet:** Die strikte Einrückung kann beim Schreiben nervig sein, ist aber extrem hilfreich, wenn das Modell die Einrückung exakt beibehalten muss (z. B. bei Quellcode oder formatiertem Text).
- **Mehrzeilige Textfelder:** Die Syntax `fieldname: |2` eröffnet ein mehrzeiliges Textfeld, das die Einrückung exakt bewahrt. In solchen Feldern muss nichts escaped werden. Das Feld endet, sobald eine Zeile mit einer geringeren Einrückung als das "Null-Level" des Textfeldes folgt.

#### 3. JSON (und JSON Lines)

JSON ist eine weitere Auszeichnungssprache, die stark in den Trainingsdaten vertreten ist.

- **Entwicklung:** Früher rieten die Autoren eher von JSON ab, da es sehr viele Escape-Sequenzen erfordert und schlechter lesbar ist.
- **Aktuelle Empfehlung:** Da insbesondere OpenAI viel Aufwand betrieben hat, damit ihre Modelle JSON akkurat generieren (weil es die Basis für deren _Tools API_ bildet), ist JSON mittlerweile eine durchaus gute und zuverlässige Wahl, zumindest für OpenAI-Modelle.

### Fazit des Abschnitts

_Structured Documents_ nutzen formale Spezifikationen (vor allem XML, YAML oder JSON), um dem Modell eine strikte Struktur vorzugeben, was das Parsen komplexer Ausgaben massiv erleichtert. Das Buch demonstriert dies am Beispiel von Anthropic's _Artifacts_, wo XML-Tags genutzt werden, um Denkprozesse (`<antThinking>`) vom eigentlichen Code-Output (`<antArtifact>`) zu trennen. Die Wahl des Formats sollte sich nach den Trainingsdaten des Modells (_Little Red Riding Hood Principle_) und den Anforderungen an Einrückungen oder Escape-Sequenzen richten.

Die Art und Weise, wie Snippets formatiert werden, hängt stark vom gewählten Dokumenttyp ab. Das Ziel ist es, die gesammelten Informationen nahtlos in die gewählte Struktur einzubetten, damit das Modell den Kontext optimal verarbeiten kann.

### Formatierung nach Dokumenttyp

Das Buch zeigt anhand eines einfachen Wetter-API-Results (`description: sunny`, `temperature: 75`), wie dasselbe Snippet in verschiedenen Dokumenttypen formatiert wird:

1. **Advice Conversation:** Das Snippet wird in die Dialog-Turns (Hin-und-Her) eingebettet.
   - **Beispiel aus dem Buch:** Der Ratsuchende fragt: _„What's the weather like?“_, und der Assistent antwortet mit den interpolierten Daten: _„It's going to be sunny with a temperature of 75 degrees.“_
2. **Analytic Report:** Wissen wird in natürlicher Sprache ausgedrückt, oft als eigene Sektion.
   - **Beispiel aus dem Buch:** Die API-Daten werden als Markdown-Überschrift formatiert: _„#### Weather Forecast sunny with a temperature of 75 degrees“_.
3. **Structured Document:** Hier ist die Formatierung oft am einfachsten, da man die relevanten Felder des Objekts aus dem Speicher direkt serialisiert.
   - **Beispiel aus dem Buch:** Die Daten werden in XML-Tags verpackt: `<weather><description>sunny</description><temperature>75</temperature></weather>`.

### Side Remarks (Asides)

Unabhängig vom Dokumenttyp ist eine sehr nützliche Methode, um Hintergrundkontext zu kommunizieren, die explizite Nebenbemerkung (z. B. _„As an aside,...“_). Sie gibt dem Modell einen starken Hinweis, zwingt es aber nicht, die Information auf eine bestimmte Weise zu nutzen.

- **Beispiel aus dem Buch (GitHub Copilot):** Bei Code-Completions bestand das Dokument-Template aus einer Source-Code-Datei. Um Code aus anderen Dateien als Kontext bereitzustellen, wurde dieser als Code-Kommentar mit einer expliziten Nebenbemerkung eingefügt:
  `// <consider this snippet from ../skill.go>`
  `// type Skill interface { ... }`
  `// </end snippet>`
  Dies signalisierte dem Modell klar, dass der Ausschnitt nur zu Vergleichszwecken da war.

### Die vier Ziele der Snippet-Formatierung

Beim Formatieren der Snippets sollten vier Prinzipien beachtet werden:

1. **Modularity:** Snippets sollten Strings sein, die sich leicht in den Prompt einfügen oder aus ihm entfernen lassen. Idealerweise ist das Dokument wie eine Liste (bei Konversationen mit Turns) oder ein Baum (bei Reports mit Hierarchien oder strukturierten Dokumenten) aufgebaut, sodass die Snippets einfach als Listenelemente oder Baumblätter behandelt werden können.
2. **Naturalness:** Das Snippet muss sich wie ein organischer Teil des Dokuments anfühlen. Wenn das LLM Source Code vervollständigen soll, gehören Natural-Language-Informationen in Kommentare und nicht einfach unverändert zwischen die Codezeilen. Bei Reports oder Dialogen werden Daten in natürlichen Text interpoliert.
3. **Brevity:** Wenn relevanter Kontext mit weniger Tokens kommuniziert werden kann, ist das ideal.
4. **Inertness:** Die Token-Länge eines Snippets sollte idealerweise nur einmal berechnet werden. Die Tokenisierung eines Snippets darf die Tokenisierung des vorherigen oder nächsten Snippets nicht beeinflussen.

### Das Problem der Tokenisierung (More on Inertness)

Das Konzept der _Inertness_ ist in der Praxis schwieriger umzusetzen, als es scheint, da es vom verwendeten Tokenizer abhängt. Das Zusammenfügen von Strings (A + B) führt beim Tokenizer nicht immer zur einfachen Addition der einzelnen Token-Arrays.

- **Beispiele aus dem Buch:**
  - `"be" + "am"` wird zu `"beam"`. Aus ursprünglich 1 + 1 Token wird beim Zusammenfügen nur noch 1 Token (`[beam]`).
  - `"cat" + "tail"` wird zu `"cattail"`. Aus ursprünglich 1 + 1 Token werden beim Zusammenfügen plötzlich 3 Token (`[c]`, `[att]`, `[ail]`).

Um unerwartetes Verschmelzen von Wörtern zu verhindern, ist es generell ratsam, einzelne Prompt-Elemente durch Whitespace zu trennen. Das Buch warnt jedoch vor zwei spezifischen Fallstricken bei GPT-Tokenizern:

1. **Leerzeichen:** GPT-Tokenizer enthalten oft Tokens, die mit einem Leerzeichen beginnen, aber keine, die mit einem enden. Daher sollten Snippets bevorzugt mit einem Leerzeichen beginnen statt mit einem zu enden.
2. **Newlines:** GPT-Tokenizer kombinieren mehrere Newline-Zeichen. Man sollte sich strikt entscheiden, ob Snippets _niemals_ mit oder _niemals_ ohne Newline beginnen. Für Entwickler ist es meist einfacher, Newlines am Anfang von Snippets komplett zu vermeiden.

### Fazit des Abschnitts

Snippets müssen so formatiert werden, dass sie sich nahtlos und natürlich in den gewählten Dokumenttyp (Dialog, Report oder strukturiertes XML/YAML) einfügen. Dabei helfen explizite Nebenbemerkungen (_Side Remarks_), um dem Modell Kontext zu geben, ohne es in seiner Antwortfreiheit einzuschränken. Die Formatierung sollte vier Kriterien folgen: _Modularity_ (leichtes Einfügen/Entfernen), _Naturalness_ (organischer Fluss), _Brevity_ (Token-Effizienz) und _Inertness_ (unabhängige Tokenisierbarkeit). Da Tokenizer Strings beim Zusammenfügen unerwartet verschmelzen oder zerlegen können, müssen Snippets sorgfältig durch Whitespace und konsistente Newline-Regeln voneinander isoliert werden.

## Formatting Snippets

Die Art und Weise, wie man Snippet-Text formatiert, hängt stark vom gewählten Dokumenttyp ab. Das Ziel ist es, die gesammelten Informationen so in den Prompt einzubauen, dass sie sich nahtlos in die gewählte Struktur einfügen. Das Buch illustriert dies anhand eines einfachen Beispiels: der Formatierung von Wettervorhersage-Daten (`description: sunny`, `temperature: 75`).

### Formatierung nach Dokumenttyp

Je nach Archetyp des Dokuments werden die Daten unterschiedlich verpackt:

1. **Advice Conversation:** Die Information wird in einen Dialog zwischen Ratsuchendem und Assistenten eingebettet.
   - **Beispiel aus dem Buch:** Der Nutzer fragt: _„What's the weather like?“_ und der Assistent antwortet: _„It's going to be sunny with a temperature of 75 degrees.“_
2. **Analytic Report:** Wissen wird in natürlicher Sprache ausgedrückt, oft als eigener Abschnitt im Bericht.
   - **Beispiel aus dem Buch:** Eine Markdown-Überschrift `#### Weather Forecast`, gefolgt von dem Satz: _„sunny with a temperature of 75 degrees“_.
3. **Structured Document:** Hier ist es oft am einfachsten, die relevanten Felder des Objekts aus dem Speicher direkt zu serialisieren.
   - **Beispiel aus dem Buch:** Die Daten werden in XML-Tags verpackt: `<weather><description>sunny</description><temperature>75</temperature></weather>`.

### Side Remarks (Asides)

Unabhängig vom Dokumenttyp ist es eine sehr nützliche Methode, Hintergrundkontext durch explizit gekennzeichnete Nebenbemerkungen (_side remarks_) einzufügen, z. B. mit der Phrase _„As an aside, ...“_.

- **Beispiel aus dem Buch (GitHub Copilot):** Bei Code-Completions, bei denen das Dokument-Template eine Quellcodedatei war, wurde Code aus anderen Dateien als Code-Kommentar eingefügt, der explizit markiert, dass das Snippet nur zu Vergleichszwecken dient:
  ```go
  // <consider this snippet from ../skill.go>
  // type Skill interface {
  //   Execute(data []byte) (refs, error)
  // }
  // </end snippet>
  ```
  Eine solche Nebenbemerkung gibt dem Modell einen starken Hinweis (_strong hint_), zwingt es aber nicht, diese Information auf eine bestimmte Weise zu nutzen.

### Die vier Ziele beim Formatieren

Beim Formatieren der Snippets gibt es vier übergeordnete Ziele, die man anstreben sollte:

1. **Modularity:** Snippets sollten Strings sein, die sich leicht in den Prompt einfügen oder entfernen lassen. Idealerweise ist das Dokument wie eine Liste (z. B. Gesprächsrunden) oder ein Baum (z. B. hierarchische Abschnitte) aufgebaut, sodass Snippets einfach als Listenelemente oder Baumblätter behandelt werden können.
2. **Naturalness:** Das Snippet sollte sich wie ein organischer Teil des Dokuments anfühlen. Wenn das LLM Quellcode vervollständigen soll, gehören natürlichsprachliche Informationen in einen Kommentar und nicht unverändert zwischen die Codezeilen. In einem Bericht oder Dialog sollten Daten in einen passenden, natürlichen Text interpoliert werden.
3. **Brevity:** Wenn man relevanten Kontext mit weniger Tokens kommunizieren kann, ist das ein großer Vorteil.
4. **Inertness:** Man möchte die Token-Länge eines Snippets idealerweise nur einmal berechnen. Daher sollte die Tokenisierung eines Snippets die Tokenisierung des vorherigen oder nächsten Snippets nicht beeinflussen.

### Fazit des Abschnitts

Das Formatieren von Snippets erfordert, dass man sich strikt an den gewählten Dokumenttyp (Dialog, Bericht oder strukturiertes Format) hält, damit die Informationen organisch (_Naturalness_) und modular (_Modularity_) in den Prompt fließen. Explizite Nebenbemerkungen (_Side Remarks_) sind ein effektives Mittel, um dem Modell zusätzlichen Kontext zu geben, ohne es in seiner Antwortfreiheit einzuschränken. Dabei gelten die vier Prinzipien _Modularity_, _Naturalness_, _Brevity_ und _Inertness_ als Leitplanken für die Integration.

## More on Inertness

Das Konzept der _Inertness_ (Trägheit) bedeutet im Idealfall, dass man die Token-Länge eines Snippets nur einmal berechnen muss und sich diese Länge nicht ändert, wenn das Snippet in den Prompt eingebettet wird. In der Praxis hängt dies jedoch stark vom verwendeten _Tokenizer_ ab.

Das grundlegende Problem ist, dass ein Tokenizer eine zusammengesetzte Zeichenkette (String A + String B) oft völlig anders in Tokens zerlegt als die beiden Strings einzeln. Das simple Aneinanderreihen von Strings bedeutet also nicht, dass auch die Arrays der Tokens einfach konkateniert werden. Dies kann die Gesamtzahl der Tokens unerwartet erhöhen oder verringern.

**Beispiele aus dem Buch (Tabelle 6-4):**
Das Buch illustriert dieses Problem anhand von zwei Beispielen für den OpenAI-Tokenizer (GPT-3.5 und neuer):

1. **Verschmelzung (Token-Reduktion):** Die Strings `"be"` und `"am"` werden einzeln als `[be]` und `[am]` tokenisiert (jeweils 1 Token). Werden sie zu `"beam"` zusammengesetzt, erkennt der Tokenizer dies als ein einziges Wort und tokenisiert es als `[beam]`. Der _Token count_ sinkt von 1 + 1 auf 1. (Die Token-IDs ändern sich von 1395 + 309 zu 54971).
2. **Aufspaltung (Token-Erhöhung):** Die Strings `"cat"` und `"tail"` werden einzeln als `[cat]` und `[tail]` tokenisiert (jeweils 1 Token). Das zusammengesetzte Wort `"cattail"` wird jedoch völlig anders zerlegt, nämlich in `[c]`, `[att]` und `[ail]`. Der _Token count_ steigt von 1 + 1 auf 3. (Die Token-IDs ändern sich von 4719 + 14928 zu 66, 1617, 607).

### Strategien zur Wahrung der Inertness

Um unerwartete Verschmelzungen oder Aufspaltungen zu verhindern und das Token-Budget verlässlich kalkulieren zu können, gibt das Buch folgende technische Faustregeln für das Formatieren der Snippets:

1. **Trennung durch Whitespace:** Es ist generell eine gute Idee, einzelne Prompt-Elemente durch Leerzeichen (_whitespace_) zu trennen, damit sie nicht unerwartet miteinander verschmelzen.
2. **Der Umgang mit führenden und nachlaufenden Spaces:** GPT-Tokenizer verwenden oft Tokens, die mit einem Leerzeichen _beginnen_, aber keine, die mit einem Leerzeichen _enden_. Um Probleme zu vermeiden, sollte man Prompt-Elemente bevorzugen, die mit einem Leerzeichen _beginnen_, anstatt mit einem zu _enden_.
3. **Der Umgang mit Newlines:** GPT-Tokenizer fassen mehrere aufeinanderfolgende Zeilenumbrüche (_newline characters_) oft zu einem einzigen Token zusammen. Daher sollte man sicherstellen, dass Snippets entweder niemals mit einem Newline beginnen oder niemals mit einem enden.
4. **Empfehlung für Entwickler:** In der Praxis hat es sich bewährt, Newlines am _Anfang_ von Snippets konsequent zu vermeiden, da dies für App-Entwickler meist einfacher umzusetzen ist.

### Fazit des Abschnitts

_Inertness_ ist bei der Prompt-Erstellung nicht garantiert, da Tokenizer Strings bei der Konkatenation oft neu und unerwartet in Tokens zerlegen (wie die Beispiele `"beam"` und `"cattail"` zeigen). Um die Token-Länge verlässlich berechnen zu können und das _Context Window_ nicht durch unsichtbare Token-Schwankungen zu sprengen, müssen Prompt-Elemente technisch sauber getrennt werden. Die wichtigste Regel lautet: Snippets sollten idealerweise mit einem Leerzeichen beginnen und niemals mit einem Newline, um die Eigenheiten der GPT-Tokenizer nicht zu triggern.

## Elastic Snippets

Wenn man Inhalte in Snippets umwandelt, entspricht ein Informationsblock normalerweise genau einem Snippet. Es gibt jedoch Situationen, in denen ein einzelner Inhalt in mehrere Snippets aufgeteilt oder in verschiedenen Formen mit unterschiedlich viel Kontext repräsentiert werden kann.

- **Beispiel aus dem Buch:** Eine literarische Analyse, die nach der Bedeutung einer bestimmten Szene in Alex Garlands Roman _The Beach_ fragt. Das LLM wird diese spezifische Szene ohne Kontext nicht kennen. Man ruft also zwei Schlüsselmomente (Textpassagen) aus dem Buch ab.

Aufgrund des begrenzten Prompt-Spaces und der begrenzten Aufmerksamkeit des Modells kann man oft nicht einfach das gesamte Kapitel einfügen. Man steht vor der Wahl, wie viel Kontext man um die Zitate herum packt:

1. Man fügt die zwei Snippets ohne jeglichen Kontext hinzu.
2. Man fügt die zwei Snippets mit etwas Kontext um jedes herum hinzu.
3. Man fügt ein kombiniertes Snippet mit Kontext hinzu, der die beiden Teile miteinander verbindet.

Das Buch stellt zwei generelle Ansätze vor, wie man mit dieser variablen Menge an Kontext umgehen kann:

### Ansatz 1: Elastic Prompt Elements

Bei diesem Ansatz nutzt man sogenannte _elastic prompt elements_. Das sind Prompt-Elemente, die in verschiedenen Versionen vorliegen – von sehr kurz bis sehr lang.

- **Die Versionen:** Die längste Version wäre das gesamte Kapitel. Eine etwas kürzere Version ersetzt einen Absatz durch „...“. Eine noch kürzere Version ersetzt zwei Absätze durch „...“. Die kürzeste Version besteht nur aus den beiden eigentlichen Snippets, die man zitieren möchte, mit einem „...“ dazwischen und ohne weiteren Kontext.
- **Die Logik bei der Assembly:** Wenn man den Prompt zusammenstellt, stellt man nicht mehr die Frage: _„Haben wir Platz, um dieses Snippet einzufügen?“_ Stattdessen fragt das System: _„Was ist die größte Version dieses Snippets, für die wir noch Platz haben?“_

### Ansatz 2: Mehrere Prompt-Elemente mit Inkompatibilitäten

Die alternative Methode besteht darin, aus den abgerufenen Informationen mehrere, eigenständige Prompt-Elemente zu erstellen.

- **Die Elemente:** Man erstellt beispielsweise ein Element, das nur die erste relevante Textpassage enthält. Ein zweites Element enthält diese Passage plus etwas Kontext. Ein drittes Element enthält noch mehr Kontext.
- **Die Regel:** Da sich diese Elemente überschneiden, muss man zwingend darauf achten, dass am Ende nur _eines_ davon tatsächlich in den Prompt aufgenommen wird.
- **Die Voraussetzung:** Dieser Ansatz erfordert eine _prompt assembly engine_ (eine Prompt-Zusammenstellungs-Logik), die in der Lage ist, Prompt-Elemente als _incompatible_ (unvereinbar / sich gegenseitig ausschließend) zu deklarieren.

### Fazit des Abschnitts

Wenn der verfügbare Platz im Prompt begrenzt ist, aber ein Informationsblock theoretisch mit unterschiedlich viel Kontext geliefert werden könnte, bieten sich _Elastic Snippets_ an. Man kann entweder ein einzelnes _elastic prompt element_ mit verschiedenen Längen-Versionen anlegen und dynamisch die größtmögliche Variante wählen, die in das Token-Budget passt. Alternativ erstellt man mehrere sich überschneidende Prompt-Elemente, die das System als _incompatible_ markiert, sodass die Assembly-Logik sicherstellt, dass immer nur die detailreichste Variante ausgewählt wird, die gerade noch hineinpasst.

## Relationships Among Prompt Elements

Prompt-Elemente existieren nicht im Vakuum: Ein Prompt ist immer eine Verschmelzung (_amalgam_) mehrerer dieser Elemente. Jeder Algorithmus, der diese Elemente zu einem finalen Prompt kombiniert, muss drei fundamentale Beziehungsdimensionen berücksichtigen: _Position_, _Importance_ und _Dependency_.

### 1. Position

Die _Position_ bestimmt, wo jedes Element im Prompt erscheinen soll. Prompt-Elemente müssen in der Regel einer spezifischen Reihenfolge folgen. Das bloße Überspringen ist manchmal möglich, aber ein Umstellen kann das Dokument für das Modell verwirrend machen.

- **Reihenfolge-Regeln aus dem Buch:**
  - Bei Zitaten aus Referenzdokumenten muss die Originalreihenfolge gewahrt bleiben (man setzt das zweite Snippet nicht vor das erste).
  - In Chats oder Narrativen gilt die chronologische Reihenfolge.
  - In anderen Fällen müssen Elemente in den korrekten Sektionen landen (z. B. gehört die Beschreibung eines Buches, das der Nutzer mag, nicht in die Sektion „Bücher, die ich wirklich hasse“).
- **Technische Umsetzung:** Um diese Beziehungen zu managen, kann man ein Array, eine Linked List, einen Index oder einen eindeutigen Positionswert für jedes Element verwenden. Oft spiegelt die Reihenfolge wider, wie die Informationen gesammelt wurden (z. B. beim zeilenweisen Scannen eines Dokuments), sodass man neue Elemente einfach am Ende anhängt.

### 2. Importance

Die _Importance_ bestimmt, wie entscheidend es ist, ein Prompt-Element einzufügen, um relevante Informationen zu übermitteln.

- **Die Verwechslungsgefahr:** Anfänger verwechseln _Position_ oft mit _Importance_, da beide häufig korrelieren (aktuelle Informationen am Ende sind oft wichtig). Das Buch warnt jedoch vor Ausnahmen: Die _Introduction_ am Anfang ist oft viel wichtiger als viele Details in der Mitte, die zu Recht im _Valley of Meh_ landen.
- **Der Trade-off:** Man muss abwägen, ob man große Blöcke relevanter Informationen einfügt oder viele kleinere, weniger kritische Elemente. Kurze, effiziente Prompt-Elemente sind oft besser als längere, die dieselbe Menge an Information transportieren.
- **Messung der Wichtigkeit:** Man sollte sich für eine Methode entscheiden und diese konsistent anwenden:
  1.  **Numerischer Score:** Eine feine Abstufung über Zahlen.
  2.  **Diskrete Prioritäts-Stufen (_discrete priority tiers_):** Eine kleine Anzahl von Ebenen, nach denen man sortiert (die unteren Stufen werden bei Platzmangel zuerst gekürzt).
- **Beispiele für Stufen aus dem Buch:**
  - _Höchste Stufe:_ Zentrale Instruktionen und die Beschreibung des Ausgabeformats (diese müssen unter allen Umständen enthalten sein).
  - _Zweithöchste Stufe:_ Erklärungen.
  - _Dritte Stufe:_ Kontext.
- **Hinweis:** Wenn die Länge nicht von vornherein berücksichtigt wird, muss die Prompt-Assembly-Engine in der Lage sein, die _Importance_ später basierend auf der tatsächlichen Token-Länge anzupassen. Das Zuweisen von Wichtigkeit erfordert menschliches Urteilsvermögen und muss im weiteren Verlauf (siehe Kapitel 10) getestet und verfeinert werden.

### 3. Dependency

Die _Dependency_ (Abhängigkeit) ist die dritte Beziehungsart. Sie fokussiert darauf, wie die Inklusion eines Elements die Inklusion anderer Elemente beeinflusst. In der Praxis fallen Abhängigkeiten meist in zwei Kategorien:

1.  **Requirements (Anforderungen):**
    Ein Prompt-Element ist von einem anderen abhängig und setzt dieses voraus.
    - **Beispiel aus dem Buch:** Man muss zwingend etablieren, dass _„Richard is the protagonist of The Beach“_, bevor man das Element einfügt, das besagt: _„He grew up in England“_.
2.  **Incompatibilities (Unvereinbarkeiten):**
    Ein Prompt-Element schließt ein anderes aus. Das passiert oft, wenn dieselbe Information auf unterschiedliche Arten präsentiert werden kann.
    - **Szenario aus dem Buch:** Man hat dieselbe Information einmal als kurze Zusammenfassung (_summary_) und einmal als detaillierte Erklärung vorliegen.
    - **Lösungsansatz:** Wenn die Prompt-Assembly-Engine damit umgehen kann, fügt man beide Versionen in die Auswahl ein, versieht sie aber mit einem Ausschluss-Hinweis (_exclusion note_). Die Engine nutzt dann automatisch die längere Version, wenn genug Platz im Token-Budget ist, und fällt auf die kürzere Version zurück, wenn der Platz nicht reicht.

### Fazit des Abschnitts

Beim Zusammenstellen eines Prompts müssen die Elemente in drei Dimensionen zueinander in Beziehung gesetzt werden: Die **Position** erzwingt eine logische oder chronologische Reihenfolge, die **Importance** priorisiert Elemente über Scores oder Stufen (wobei die wichtigste Instruktion immer Vorrang vor Kontext hat), und **Dependency** regelt logische Voraussetzungen (_Requirements_) sowie sich gegenseitig ausschließende Alternativen (_Incompatibilities_). Nur wenn die Assembly-Logik alle drei Dimensionen berücksichtigt, entsteht ein kohärenter und effektiver Prompt.

## Putting It All Together

Das Zusammenstellen des finalen Prompts ist im Kern ein Optimierungsproblem. Das Ziel der _Prompt Assembly Engine_ ist es, diejenigen Prompt-Elemente auszuwählen und anzuordnen, die den Gesamtwert des Prompts maximieren.

Dabei muss das System zwei harte Nebenbedingungen (_constraints_) berücksichtigen:

1. **Dependency structure:** Alle _Requirements_ (logische Voraussetzungen) und _Incompatibilities_ (Unvereinbarkeiten) zwischen den Elementen müssen zwingend eingehalten werden.
2. **Prompt length:** Die Gesamtlänge muss innerhalb eines festen Limits bleiben. Dieses Limit ist in der Regel die Größe des _Context Windows_ abzüglich der Tokens, die für die Antwort des Modells reserviert sind. Bei sehr großen _Context Windows_ kann man auch ein weicheres Token-Budget nutzen, das auf der verfügbaren Rechenleistung (_compute_) basiert, um zu viele irrelevante Kontextdaten zu vermeiden.

Das Buch vergleicht dieses Problem mit _Linear Programming_ und dem _0-1 Knapsack Problem_ (Rucksackproblem), weist aber darauf hin, dass das klassische Rucksackproblem keine Abhängigkeiten zwischen den Objekten kennt. Da es kein Standard-Tool für diese spezifische Aufgabe gibt, müssen Entwickler ihre eigene Logik schreiben.

- **Beispiel aus dem Buch (GitHub Copilot):** Bei Code-Completions benötigen Snippets oft einen spezifischen _Postfix_. Dieser Umstand wird mit speziellen, benutzerdefinierten Funktionen (_custom functions_) gelöst, die die Abhängigkeiten zwischen den Codezeilen verwalten.

### Der iterative Ansatz: Der minimale Prompt Crafter

Wenn man eine Anwendung iterativ entwickelt, sollte man mit einer minimalen Version eines _Prompt Crafters_ beginnen, um schnell zu testen, ob die App-Idee Potenzial hat.

- **Funktionsweise:** Dieser einfache Ansatz bewertet oder priorisiert Snippets nicht aktiv. Er sortiert die Elemente und nutzt schlicht den hinteren Teil (das Suffix) des gesammelten Inhalts, bis das Token-Budget ausgeschöpft ist.
- **Warum das funktioniert:** LLMs sind darauf trainiert, gut mit Dokument-Suffixen umzugehen. Zudem ist dieser Ansatz ideal für Anwendungen, die auf einem Haupttext aufbauen, oder für chatartige Anwendungen, bei denen die jüngsten Interaktionen am relevantesten sind.

### Fortgeschrittene Engines: Greedy Algorithms

Wenn die Anwendung wächst, benötigt man ausgefeiltere _Prompt-Crafting-Engines_. Für eine schnelle Ausführung empfiehlt das Buch den Einsatz von _Greedy Algorithms_ (gierige Algorithmen), die es in zwei Hauptvarianten gibt:

#### 1. Additive greedy approach

Bei diesem Ansatz startet man mit einem leeren Prompt und fügt Schritt für Prompt das jeweils wertvollste Element hinzu.

- **Die Regel:** In jedem Schritt wird das Element mit dem höchsten Wert gewählt, das alle _Requirements_ erfüllt, nicht mit bestehenden Elementen kollidiert (_Incompatibilities_) und noch in das verbleibende Prompt-Budget passt.
- **Einsatzgebiet:** Dieser Ansatz ist sehr effektiv, wenn es viel mehr Elemente gibt, als in den Prompt passen, und viele eliminiert werden müssen.
- **Einschränkungen:** Er funktioniert schlecht bei zyklischen Abhängigkeiten oder wenn extrem wertvolle Elemente von unwertigen Elementen abhängen.
- **Optimierung:** Man kann den Prozess vereinfachen, indem man die Elemente basierend auf ihren Abhängigkeiten und Werten sortiert, sodass ein Element erst in die engere Wahl gezogen wird, wenn alle seine Voraussetzungen bereits erfüllt sind.

#### 2. Subtractive greedy approach

Bei diesem Ansatz startet man damit, alle gesammelten Prompt-Elemente einzufügen, und entfernt dann schrittweise die weniger wertvollen Elemente oder solche, deren Abhängigkeiten durch das Entfernen anderer Elemente nicht mehr erfüllt sind.

- **Einsatzgebiet:** Dieser Ansatz eignet sich gut, wenn die Anzahl der Elemente überschaubar ist und es nur wenige _Incompatibilities_ gibt.
- **Einschränkungen:** Wenn hochwertige Elemente von minderwertigen abhängen, kann dies zu suboptimalen Ergebnissen führen (es sei denn, man nutzt fortgeschrittene Techniken, um die Abhängigkeiten hochwertiger Elemente aktiv zu schützen).
- **Vorteil:** _Elastic Snippets_ (elastische Schnipsel) lassen sich in diesem subtraktiven Ansatz in der Regel deutlich leichter handhaben als im additiven.

### Fazit des Abschnitts

Die finale Prompt-Assembly ist ein Optimierungsproblem, das _Dependencies_ und das _Prompt length_-Limit balancieren muss. Da es keine Standardlösung gibt, müssen Entwickler eigene Algorithmen schreiben. Für den Start eignet sich ein minimaler _Prompt Crafter_, der einfach das Ende des Kontexts bis zum Limit auffüllt. Für komplexere Anwendungen bieten sich _Greedy Algorithms_ an: Der _additive approach_ baut den Prompt von null schrittweise mit den wertvollsten kompatiblen Elementen auf, während der _subtractive approach_ mit dem vollen Kontext startet und unwichtige Teile abstutzt. Beide Ansätze sind jedoch nur grundlegende Prototypen, die im Laufe der Projektentwicklung an die spezifischen Anforderungen der Anwendung angepasst werden müssen.

# Kapital 7

## The Preamble

Im Kontext von Completions (den generierten Antworten des Modells) ist das **Preamble** der anfängliche Teil des generierten Textes, der die Bühne für den Hauptinhalt bereitet. Manchmal ist dies hilfreich, aber oft führt es dazu, dass Completions mit uninteressanten oder nutzlosen Details beginnen, bevor sie zur eigentlichen Lösung des Problems kommen. Das ist nicht nur ärgerlich, sondern auch kostspielig: Das Generieren von Token kostet Zeit (Latency) und Rechenleistung (Ressourcen und Geld). Text zu produzieren, den man am Ende nicht verwendet, ist verschwenderisch – aber in manchen Fällen auch absolut wünschenswert.

Ob es wirklich verschwenderisch ist oder vermieden werden kann, hängt von der genauen Art des Preambles ab. Das Buch unterscheidet drei verschiedene Arten von Preambles:

### 1. Structural Boilerplate

Dies ist der Text zwischen dem Ende eines Prompts und dem Beginn einer Completion.

- Bei der Verwendung eines reinen Completion-Modells (im Gegensatz zu Chat-Modellen) könnte man diese Art von Preamble theoretisch eliminieren.
- Es ist jedoch effizienter, deterministisches Boilerplate direkt in den Prompt aufzunehmen, anstatt es das Modell generieren zu lassen. Das stellt sicher, dass das Modell das gewünschte Format einhält, und macht den Prozess schneller und günstiger.
- Structural Boilerplate eignet sich generell gut als Übergang (Transition) vom Prompt zur Completion.

### 2. Reasoning

Gegen Ende 2023 begann ChatGPT damit, eine leicht interpretierte Version der Fragen widerzuspiegeln (Mirroring), um das Verständnis zu klären und potenzielle Missverständnisse hervorzuheben.

- Dieser Ansatz hilft dem Modell, bessere Schlussfolgerungen zu ziehen, indem es sich auf die Schlüsselaspekte des Prompts konzentriert, und sorgt so für genauere Antworten.
- Zusätzlich hilft **Chain-of-Thought Prompting** dem Modell, Probleme in handhabbare Teile zu zerlegen. Der detaillierte Denkprozess ist dabei fast immer Teil des Preambles und nicht der Hauptantwort.
- Wenn man Chain-of-Thought Prompting anwendet, ist ein langes Preamble eine Tugend (_a virtue, not a vice_), selbst wenn es deutlich länger ist als die eigentliche finale Antwort.
- **Beispiel aus dem Buch (Figure 7-2):** Das Buch zeigt ein Beispiel, bei dem die Antwort, die nach einem langen Reasoning-Preamble erzielt wird, korrekt ist, während die Antwort nach einem kurzen Preamble falsch ist. Viele der fortgeschrittenen Prompting-Techniken aus Kapitel 8 (Conversational Agency) basieren darauf, Reasoning Preambles effektiv zu nutzen.

### 3. Fluff

Mit RLHF (Reinforcement Learning from Human Feedback) trainierte Modelle neigen oft zu ausführlichen und übermäßig höflichen Antworten. Das kann für die programmatische Nutzung, wo knappe und maschinenlesbare Outputs benötigt werden, sehr problematisch sein.

- Auch Modelle ohne RLHF produzieren gelegentlich unnötiges **Fluff** (Fülltext/Geplänkel).
- Um dies zu steuern, kann man Techniken wie Anweisungen mit Few-Shot-Beispielen verwenden oder Prompts so umformatieren, dass die Hauptantwort von zusätzlichen Kommentaren getrennt wird. Das kann jedoch teuer (im Sinne von Token-Kosten) sein.
- Bei strukturierten Dokumenten (wie JSON oder YAML) halten Modelle das Format im Allgemeinen ein. Bei Freitext-Kontexten (_free-form contexts_) hilft es, nach der Hauptantwort _zuerst_ zu fragen, gefolgt von zusätzlichen Informationen. Das erleichtert das Parsen und reduziert die Auswirkungen von Fluff.
- Welche Teile des Fluffs man ans Ende verschieben (reservieren) sollte, hängt davon ab, welche Art von Fluff das gewählte Modell für die spezifischen Fragen der Anwendung typischerweise liefert. Typische Kandidaten für Fluff sind: Kommentare, Haftungsausschlüsse (_disclaimers_), Hintergrundinformationen und Erklärungen.
- **Beispiele aus dem Buch (Figures 7-3 und 7-4):** Das Buch demonstriert, wie man ChatGPTs Fluff in einen nachgelagerten Punkt verbannt (z. B. durch das Format `1. [Hauptantwort], 2. [Erklärung]`), damit er leicht herausgefiltert (_parsed out_) werden kann. Das Buch warnt jedoch: Dieser Trick ist gut darin, das meiste Fluff hinter die Hauptantwort zu verbannen, aber er wird nicht immer eine kurze, höfliche Einleitung vor dem ersten nummerierten Listenpunkt los (wie in Figure 7-3 gezeigt, wo ChatGPT gegen explizite Anweisungen im zweiten Anlauf trotzdem eine Fluff-Einleitung inkludierte).

### Fazit des Abschnitts

Das **Preamble** einer Completion ist der einleitende Text, den das Modell vor der eigentlichen Hauptantwort generiert. Er kostet Token und damit Latenz und Geld. Man unterteilt ihn in drei Kategorien: **Structural Boilerplate** (die man besser fest in den Prompt verschiebt, um Kosten zu sparen und Formate zu erzwingen), **Reasoning** (wie bei Chain-of-Thought Prompting, wo ein langes Preamble essenziell für die Korrektheit der Antwort ist) und **Fluff** (höflicher Fülltext durch RLHF, den man durch geschicktes Prompt-Design ans Ende der Antwort verbannen sollte, um ihn beim Parsen leicht ignorieren zu können).

# Kapital 8

## Tool Usage

Isolierte Sprachmodelle (Language Models) sind in ihren Fähigkeiten stark eingeschränkt, wenn sie nur für sich allein arbeiten. Ein reines Chat-Modell kennt ausschließlich die Informationen, die während seines Trainings verfügbar waren, sowie das, was der Nutzer ihm im aktuellen Gespräch mitgeteilt hat. Es kann nicht eigenständig in die Welt hinauszugreifen, um neue Informationen zu beschaffen, und es kann keine externen Aktionen für den Nutzer ausführen.

Das Buch identifiziert drei fundamentale Limitierungen von isolierten Chat-Modellen:

1. **Fehlender Zugriff auf "Hidden Knowledge" (Verstecktes Wissen):**
   Im Arbeitsalltag nutzen Menschen ständig private oder interne Informationen (Firmendokumentation, interne Memos, Chat-Nachrichten, Code), auf die das Modell keinen Zugriff hat. Zudem leben wir in der Gegenwart, nicht in der Vergangenheit. Das Modell weiß nichts über aktuelle API-Änderungen einer genutzten Bibliothek oder über aktuelle Nachrichtenereignisse, was seine Antworten irreführend oder falsch macht.
   - **Beispiel aus dem Buch:** Wenn man Reisepläne erstellt, benötigt man Echtzeit-Informationen darüber, welche Flüge _jetzt gerade_ verfügbar sind. Ein nacktes Chat-Modell hat auf keinerlei solche aktuellen Daten Zugriff.
2. **Schwächen bei bestimmten Aufgaben (insbesondere Mathematik):**
   Sprachmodelle sind von Natur aus nicht gut in Mathematik.
   - **Beispiel aus dem Buch:** Wenn man ChatGPT eine einfache Arithmetikaufgabe stellt, liefert es oft die richtige Antwort, weil es quasi alle einfachen Aufgaben auswendig gelernt hat. Sobald die Zahlen jedoch größer oder die Berechnungen komplexer werden, liefert das Modell immer schlechtere Schätzungen. Das Problem: Diese Fehler werden oft mit absoluter Selbstverständlichkeit und als Wahrheit präsentiert.
3. **Fehlende Handlungsfähigkeit in der echten Welt:**
   Für sich allein genommen "tun" Chat-Modelle absolut nichts – sie reden nur. Die einzige Möglichkeit, wie sie eine Veränderung in der echten Welt bewirken können, ist, den Nutzer zu bitten, etwas für sie zu erledigen.
   - **Beispiel aus dem Buch:** Ein isoliertes Modell kann keine Flugtickets kaufen, keine E-Mails versenden und nicht die Temperatur an einem Thermostat ändern.

### Die Lösung: Tool Usage

Um all diese Probleme zu lösen, nutzt die LLM-Community **Tool Usage**. Das Ziel ist es, dem Modell Zugriff auf aktuelle Informationen zu geben, ihm bei nicht-sprachlichen Aufgaben (wie Rechnen) zu helfen und ihm zu ermöglichen, mit seiner Umgebung zu interagieren.

Das Grundprinzip ist einfach:

1. Man erzählt dem Modell, welche Tools ihm zur Verfügung stehen, und erklärt ihm, wann und wie es diese nutzen soll.
2. Das Modell nutzt diese Informationen, um externe APIs aufzurufen (Tool Invocation).
3. Es liegt in der Verantwortung der umgebenden Anwendung (Application), den Tool-Aufruf aus der Text-Ausgabe des Modells zu parsen (extrahieren).
4. Die Anwendung leitet die Anfrage an die entsprechende reale API in der echten Welt weiter.
5. Die Anwendung nimmt die resultierenden Informationen aus der API und baut sie in zukünftige Prompts ein, die an das Modell gesendet werden.

### Fazit des Abschnitts

Isolierte LLMs sind durch ihr statisches Trainingswissen, ihre mathematischen Schwächen und ihre fehlende Fähigkeit, in der echten Welt zu handeln, stark limitiert. **Tool Usage** löst dieses Problem, indem das Modell externe APIs aufruft, um Echtzeitdaten zu beziehen, Berechnungen auszulagern und Aktionen in der echten Welt durchzuführen. Die technische Brücke bildet dabei die umgebende Anwendung, die die Tool-Aufrufe des Modells abfängt, in der realen Welt ausführt und die Ergebnisse wieder in den Kontext des Modells zurückführt.

## LLMs Trained for Tool Usage

### Defining and using tools

Um einem Modell den Zugriff auf externe Tools zu ermöglichen, wird ein strukturierter Prozess durchlaufen, der die Definition der Tools, deren Repräsentation im Prompt und die Verarbeitung der Aufrufe durch die umgebende Anwendung umfasst.

Der Ablauf lässt sich in folgende Schritte unterteilen:

1. **Einrichten der Funktionen:** Zuerst werden die tatsächlichen Funktionen definiert, die in die reale Welt hinausgreifen, Informationen sammeln oder Änderungen vornehmen. Im Buch wird dies anhand von Python-Mock-ups demonstriert.
2. **Repräsentation als JSON-Schema:** Die Funktionen werden als JSON-Schema dargestellt, damit die API sie im Prompt an das Modell übergeben kann. Das Schema deklariert den Namen, eine Beschreibung und die Parameter (inklusive Typ, Beschreibung und erforderlicher Felder).
3. **Erstellen eines Lookup-Dictionarys:** In der Anwendung wird ein Dictionary (z. B. `available_functions`) angelegt, um die Tools bei Bedarf über ihren Namen abzurufen und auszuführen.
4. **Nachrichtenverarbeitung (`process_messages`):** Ein Algorithmus in der Anwendung steuert den Interaktions-Loop in 5 Schritten:
   1. Senden der Nachrichten zusammen mit den Tool-Definitionen an das Modell.
   2. Anhängen der Modellantwort (die einen Tool-Aufruf oder normalen Text enthalten kann) an den Konversationsverlauf.
   3. Prüfen, ob das Modell ein Tool aufrufen wollte (`tool_calls`).
   4. Extrahieren des Tool-Aufrufs (Funktionsname und Argumente) und Ausführen der tatsächlichen Funktion in der Anwendung.
   5. Erweitern der Konversation um die Funktionsantwort (mit der Rolle `"tool"`), damit das Modell das Ergebnis in zukünftigen Turns sehen und darauf reagieren kann.

**Beispiele aus dem Buch:**

- **Thermostat-Beispiel:** Der Nutzer fragt: _"Can you make it a couple of degrees warmer in here?"_. Das Modell ruft zuerst `get_room_temp` auf und erhält als Antwort "74". Daraufhin ruft es `set_room_temp` mit dem Argument `{"temp": 76}` auf (2 Grad wärmer). Abschließend generiert das Modell eine natürliche Antwort für den Nutzer: _"The room temperature was 74ºF and has been increased to 76°F."_
- **GitHub Copilot:** Die Autoren stellten fest, dass das Modell die GitHub-Code-Search-Syntax aus seinen Trainingsdaten kannte. Indem sie die Argumente exakt so benannten und formatierten wie in der originalen Dokumentation, war es für das Modell weniger verwirrend, das Tool korrekt aufzurufen.

### Under the Hood (Interne Repräsentation)

Obwohl Tool Calling anders wirkt als reine Textvervollständigung, handelt es sich intern ebenfalls um ein fine-getuntes Modell mit "syntactic sugar" auf API-Ebene.

- **Platzierung im Prompt:** Die Tool-Definitionen werden im System-Message direkt nach der vom Nutzer bereitgestellten Anweisung als Teil des Dokuments im ChatML-Format platziert.
- **Little Red Riding Hood Principle:** Die interne Struktur nutzt Markdown, um die Antwort zu organisieren. Da Markdown ein häufiges Muster in den Trainingsdaten ist, versteht das Modell die implizierte Struktur sofort.
- **TypeScript-Darstellung:** Die Tools werden intern so dargestellt, als wären es TypeScript-Funktionen. Das ist aus drei Gründen clever:
  1. TypeScript ermöglicht eine reichhaltigere Vocabulary für Typdefinitionen, was sicherstellt, dass das Modell die Argumente mit den korrekten Typen formatiert.
     a>
  2. Dokumentation lässt sich leicht in die Funktionsdefinition integrieren (sowohl für die Funktion selbst als auch für einzelne Argumente).
  3. Die Definition zwingt das Modell dazu, die Funktion mit einem JSON-Objekt aufzurufen, das die Argumentnamen explizit auflistet. Dies macht Aufrufe konsistent und "nachdenklicher": Das Modell muss den Parameternamen (z. B. `temp`) direkt vor den Wert schreiben, was das Risiko von Fehlern durch positionale Argumente verringert.
- **Token-für-Token-Klassifikation:** Jeder Token im Tool-Aufruf dient einem spezifischen Zweck, um das Problem hierarchisch einzugrenzen. Das Modell agiert dabei wie ein Klassifikationsalgorithmus:
  1. Wer spricht? (Die API erzwingt `<|im_start|>assistant`).
  2. Soll ein Tool aufgerufen werden? (Das Modell generiert `to=functions.`).
  3. Welches Tool? (Das Modell generiert den Funktionsnamen, z. B. `set_room_temp`).
  4. Welches Argument? (Das Modell generiert den Schlüssel, z. B. `{"temp":`).
  5. Welcher Wert? (Das Modell generiert den Wert, z. B. `76`).
  6. Sind wir fertig? (Das Modell schließt mit `}<|im_end|>`).

### Fazit des Abschnitts

LLMs, die für Tool Usage trainiert sind, nutzen eine Kombination aus JSON-Schema-Definitionen auf API-Ebene und einer internen Repräsentation als TypeScript-Funktionen im System-Prompt. Diese Struktur maximiert die Konsistenz und Genauigkeit der Argumentübergabe durch das _Little Red Riding Hood Principle_ und die erzwungene explizite Benennung von Parametern. Die umgebende Anwendung übernimmt dabei die essentielle Brückenfunktion: Sie parst die Textgenerierung des Modells, führt die Funktion in der realen Welt aus und speist das Ergebnis als neue Nachricht mit der Rolle `"tool"` zurück in den Kontext.

## Guidelines for Tool Definitions

Dieser Abschnitt liefert allgemeine Richtlinien für das Design und die Beschreibung von Tools für Conversational Agents. Die Empfehlungen basieren auf zwei grundlegenden Intuitionen:

1. Was für einen Menschen einfacher zu verstehen ist, ist auch für ein LLM einfacher zu verstehen.
2. Die besten Ergebnisse erzielt man, wenn man Prompts nach den Mustern der Trainingsdaten gestaltet (das sogenannte **Little Red Riding Hood Principle**).

### Selecting the right tools

1. **Anzahl begrenzen:** Je mehr Tools dem Modell zur Verfügung stehen, desto höher ist die Gefahr, dass es verwirrt wird.
2. **Domäne partitionieren:** Tools sollten so viel der Domäne wie möglich abdecken, aber sich nicht überschneiden. Es sollten keine Tools definiert werden, die ähnliche Aktionen ausführen.
3. **Einfachheit:** Einfachere Tools sind besser.
4. **Keine 1:1 Web-APIs:** Man sollte seine Web-API nicht einfach in den Prompt kopieren. Web-APIs haben oft zu viele Parameter und komplexe Responses. Das beschreibt zu viel Text, frisst das Token-Budget auf und macht es für das Modell schwieriger, das Tool korrekt aufzurufen.

### Naming tools and arguments

Namen sollten aussagekräftig und selbsterklärend sein. Das Modell liest die Namen (genau wie ein Mensch eine API-Spezifikation) und baut sich Erwartungen über den Zweck der Tools und Argumente auf.

- Für OpenAI-Modelle, die Tools intern als TypeScript darstellen, sollte man **Camel-Case**-Naming-Konventionen verwenden.
- Man sollte Namen vermeiden, die einfach nur kleingeschriebene Wortkombinationen sind (z. B. `retrieveemail`), da diese schwerer zu parsen sind.

### Defining tools

Definitionen sollten so einfach wie möglich gehalten werden, aber genug Details enthalten, damit das Modell (oder ein Mensch) versteht, wie das Tool zu nutzen ist.

- **Kein "Legalese":** Wenn Definitionen wie Juristendeutsch klingen, führt man zu viele Konzepte ein, die den begrenzten **Attention Mechanism** des Modells überfordern.
- **Keine Mehrdeutigkeiten:** Wenn das Tool legitimerweise eine detaillierte Erklärung erfordert, muss die Definition absolut eindeutig sein, damit das Modell nicht darüber stolpert.
- **An Trainingsdaten anlehnen:** Wenn man mit einer öffentlichen API arbeitet, die das Modell aus dem Training kennt, sollte man eine vereinfachte Version erstellen, die das Naming, die Konzepte und den Stil der Original-API beibehält.

**Beispiel aus dem Buch:**

- **GitHub Copilot:** Die Autoren stellten fest, dass das genutzte OpenAI-Modell die GitHub-Code-Search-Syntax auswendig kannte (es konnte die Dokumentation quasi aufsagen). Es war für das Modell viel weniger verwirrend, wenn die Argumente exakt so benannt wurden und das Format der Argumentwerte exakt dem der Original-Dokumentation entsprach.

### Dealing with arguments

Argumente sollten nach Möglichkeit wenige und einfach sein.

1. **JSON-Schema-Typen:** Typen wie `string`, `number`, `integer` und `boolean` funktionieren problemlos. Modifikatoren wie `enum` und `default` helfen, die Nutzung durch das Modell zu konditionieren.
2. **Ignorierte Modifikatoren:** Bei OpenAI-Modellen (Stand der 1106-Modelle von November 2023) werden bestimmte JSON-Schema-Eigenschaften wie `minItems`, `uniqueItems`, `minimum`, `maximum`, `pattern` und `format` im internen Prompt _nicht_ repräsentiert. Auch die Beschreibungen von verschachtelten Parametern (nested parameters) tauchen im Prompt nicht auf.
3. **Vorsicht bei langen Text-Inputs:** Besonders bei OpenAI-Modellen ist Vorsicht bei langen Textargumenten geboten. Da Argumente in JSON gestopft werden, müssen Newlines und Anführungszeichen escaped werden. Je mehr Text vorhanden ist, desto wahrscheinlicher vergisst das Modell ein Escape-Zeichen. Das Problem verschlimmert sich massiv bei Code, der voller Newlines und Anführungszeichen ist.
   - _Hinweis zu Anthropic:_ Anthropic kodiert Function Calls mit XML-Tags statt JSON, wodurch Argumente nicht escaped werden müssen. Daher ist Claude prinzipiell besser für lange Textargumente geeignet.
4. **Argument Hallucination:** Das Modell erfindet Placeholder-Werte für Argumente, wenn die tatsächlichen Werte nicht im Gespräch erwähnt wurden.
   - **Beispiel aus dem Buch:** Tools, die `org`- und `repo`-Argumente benötigen, neigen dazu, Platzhalter wie `"my-org"` und `"my-repo"` zu erfinden.
   - **Lösungsansätze:**
     1. Wenn der Wert in der Anwendung bekannt ist, sollte man das Argument aus der Funktionsdefinition entfernen (oder einen Default-Wert setzen), damit das Modell nicht verwirrt wird.
     2. Das Modell anweisen nachzufragen, wenn es unsicher ist. (Die Autoren warnen: Das Modell wird das oft trotzdem nicht tun, auch wenn die Technologie hier schnell besser wird).

### Dealing with tool outputs

- In den Tool-Definitionen sollte das Modell antizipieren können, was es im Output vorfinden wird. Das Modell kommt mit freiem Text (Natural Language) oder strukturiertem JSON gleichermaßen gut zurecht.
- **Kein "Just-in-Case"-Content:** Man sollte keine zusätzlichen Inhalte in den Output packen, nur "falls sie hilfreich sein könnten". Modelle lassen sich leicht durch irrelevante Inhalte (sogenannte **spurious content**) ablenken.

### Dealing with tool errors

- Fehlerinformationen sind wertvoll, da das Modell sie nutzen kann, um Korrekturen vorzunehmen.
- Man sollte nicht einfach den rohen internen Error-Text der Anwendung in die Tool-Response kopieren. Die Fehlermeldung muss im Kontext der Tool-Definition für das Modell Sinn ergeben.
- **Validierungsfehler:** Dem Modell genau sagen, was es falsch gemacht hat, damit es einen neuen Versuch starten kann.
- **Andere Fehler:** Sicherstellen, dass die Fehlermeldung hilfreiche Informationen für das Modell enthält.

### Executing “dangerous” tools

Wenn ein Modell Tools ausführt, die Änderungen in der echten Welt vornehmen, muss man die Nutzer vor unbeabsichtigten Nebenwirkungen schützen.

1. **Niemals auf Prompt-Ebene verlassen:** Man sollte dem Modell _nicht_ einfach in der Tool-Beschreibung den Befehl geben: _"Make sure to double-check with the user before you run this."_ Modelle sind inhärent unzuverlässig. Bei dieser Strategie wird garantiert in einem kleinen Prozentsatz der Fälle genau das passieren, was man verboten hat.
2. **Interception in der Application Layer:** Man sollte das Modell nicht daran hindern, das Tool aufzurufen (es darf ruhig den Befehl geben, das gesamte Geld an das Konto der Ex-Frau zu überweisen). Stattdessen muss die **Application Layer** (die umgebende Anwendung) alle derartigen gefährlichen Requests abfangen und explizit die Freigabe (Sign-off) des Nutzers einholen, _bevor_ die Anwendung die echte API aufruft und einen katastrophalen Fehler baut.

### Fazit des Abschnitts

Die Gestaltung von Tool-Definitionen folgt dem Prinzip der menschlichen Lesbarkeit und der Orientierung an Trainingsdaten (**Little Red Riding Hood Principle**). Tools sollten einfach, klar benannt und in ihrer Anzahl begrenzt sein. Bei der Definition von Argumenten müssen technische Limitierungen der Modelle beachtet werden (z. B. das Ignorieren bestimmter JSON-Schema-Regeln oder Probleme beim Escaping von langen Texten in JSON). Besonders kritisch ist der Umgang mit gefährlichen Tools: Da Modelle Anweisungen zur Rückfrage beim Nutzer unzuverlässig befolgen, muss die Sicherheit zwingend durch Abfangen und Bestätigen in der **Application Layer** gewährleistet werden, nicht durch Prompt-Engineering.

## Reasoning

LLMs wählen Token für Token aus, um eine statistisch wahrscheinliche Vervollständigung des Prompts zu liefern. Dabei demonstrieren sie zwar eine Art von Denkfähigkeit, aber es handelt sich um eine sehr oberflächliche Form des **Reasoning** (Schlussfolgerns). Das einzige Ziel des Modells – erzwungen durch seine Trainingsschichten – ist es, Text zu erzeugen, der einfach „richtig klingt“.

Da das Modell keinen **internal monologue** (inneren Monolog) besitzt, gibt es keine mentale Überprüfung einer Problemstellung, keine Abwägung, wie diese auf bekannte Fakten abgebildet werden kann, und keinen Vergleich konkurrierender Ideen. Stattdessen sagt das Modell Token für Token voraus, was am besten auf den gerade verarbeiteten Text folgt.

### Chain of Thought

Um dieses Problem zu beheben, gibt es Techniken, die dem Modell einen inneren Monolog ermöglichen, damit es sorgfältiger über ein Problem nachdenken kann, bevor es eine endgültige Antwort liefert.

Im Januar 2022 erschien das Paper „Chain-of-Thought Prompting Elicits Reasoning in Large Language Models“. Die Autoren zeigten, dass **Few-Shot Examples** verwendet werden können, um ein Modell darauf zu konditionieren, nachdenklicher und damit genauer zu antworten.

Normalerweise würde ein Modell eine Alltagsfrage (Commonsense Question) mit einem „Ja“ oder „Nein“ beantworten, gefolgt von einer Erklärung. Da das Modell jedoch keinen inneren Monolog hat, ist das initiale „Ja“ oder „Nein“ eine intuitive Vermutung, und die nachfolgende Erklärung ist lediglich eine Rationalisierung, um diese Vermutung zu rechtfertigen.

Die Autoren demonstrierten, dass das Modell mit höherer Wahrscheinlichkeit zur korrekten Antwort gelangt, wenn es zuerst über die Frage nachdenkt und _dann_ die Antwort gibt. Dies wurde erreicht, indem dem Modell Few-Shot-Beispiele gegeben wurden, die es darauf konditionieren, erst zu denken und dann zu antworten.

**Beispiele aus dem Buch:**

- **Hamster-Beispiel:**  
  _Frage:_ „Do hamsters provide food for any animals?“  
  _Antwort:_ „Hamsters are prey animals. Prey are food for predators. Thus, hamsters provide food for some animals. So the answer is yes.“
- **Birnen-Beispiel (Pear):**  
  _Frage:_ „Yes or no: would a pear sink in water?“  
  _Antwort:_ „The density of a pear is about 0.6g/cm3, which is less than water. Objects less dense than water float. Thus, a pear would float. So the answer is no.“
- **The Exorcist-Beispiel:**  
  _Frage:_ „Will The Exorcist stimulate the limbic system?“  
  _Antwort:_ „The Exorcist is a horror movie. Horror movies are scary. The limbic system is involved in fear. Thus, The Exorcist will stimulate the limbic system. So the answer is yes.“

Unter Verwendung des StrategyQA-Datensatzes und des PaLM 540B-Modells zeigte das Paper, dass dieser Stil des **Chain-of-Thought Reasoning** die Genauigkeit bei der Beantwortung von Alltagsfragen von der vorherigen State-of-the-Art-Rate von 69,4 % auf 75,6 % steigerte.

Diese Vorteile beschränkten sich nicht nur auf Alltagswissen. Bei mathematischen Textaufgaben (GSM8K-Datensatz) stieg die Lösungsrate mit dem PaLM 540B-Modell von etwa 20 % (bei Standard-Prompting) auf 60 % (mit Chain-of-Thought Reasoning). Ähnliche Verbesserungen wurden auch in anderen Domänen wie dem symbolischen Reasoning beobachtet.

#### Zero-Shot Reasoning

Ein Paper vom Mai 2022 („Large Language Models are Zero-Shot Reasoners“) verbesserte diesen Ansatz mit einem cleveren Trick. Anstatt mühsam relevante Few-Shot-Beispiele zusammenzustellen, um das Modell in ein Denkmuster zu versetzen, zeigte das Paper, dass es ausreicht, die Antwort einfach mit dem Satz „Let’s think step-by-step“ zu beginnen. Dieser Cue veranlasst das Modell, Chain-of-Thought-Reasoning zu generieren, gefolgt von einer genaueren Antwort.

#### Pause Tokens

Ein weiteres Paper vom Oktober 2023 („Think Before you Speak: Training Language Models With Pause Tokens“) trieb das Chain of Thought auf eine extreme Weise voran. Die Autoren fine-tunten ein Sprachmodell so, dass es einen „Pause“-Token verwendet. Nach einer Frage injizierten sie eine bestimmte Anzahl (z. B. 10) dieser bedeutungslosen Token in den Prompt. Der Effekt war, dass das Modell zusätzliche Zeitschritte (Timesteps) hatte, um über die Antwort nachzudenken. Die Informationen aus vorherigen Token wurden so gründlicher in den Modellzustand integriert, was zu einer besseren Antwort führte. Dies ist analog zum menschlichen Verhalten: Wir haben unsere eigenen „Pause“-Token namens „Äh“ und „Ähm“, die wir verwenden, wenn wir Zeit schinden, um nachzudenken, was wir sagen wollen.

### Fazit des Abschnitts

Der Kernpunkt ist, dass Sprachmodelle keinen inneren Monolog haben und daher keine Möglichkeit besitzen, über etwas nachzudenken, bevor sie eine Antwort „herausplatzen“ lassen. Wenn man ein Modell jedoch konditioniert, Zeit mit dem Nachdenken über das Problem zu verbringen – sei es durch Few-Shot-Beispiele oder durch eine explizite Aufforderung wie „Let’s think step-by-step“ –, ist es viel wahrscheinlicher, dass es eine qualitativ hochwertige Vervollständigung generiert.

## ReAct: Iterative Reasoning and Action

Das im Oktober 2022 erschienene Paper „ReAct: Synergizing Reasoning and Acting in Language Models“ vertieft das Konzept des Reasonings, indem es Situationen betrachtet, die Informationsabruf (Information Retrieval) und mehrstufige Problemlösung erfordern. Es war eines der ersten Papers, das externe Tools in diesen Prozess integrierte.

### Das Grundkonzept und die Tools

Anstatt nur eine direkte Antwort zu generieren, soll das Modell iterativ über das Problem nachdenken (**Think**), eine Aktion ausführen, um Informationen zu sammeln (**Act**), und das Ergebnis dieser Aktion beobachten (**Observe**). Dieser **Think-Act-Observe-Loop** wird so lange wiederholt, bis das Modell über alle notwendigen Informationen verfügt, um die Aufgabe abzuschließen.

Für das im Buch beschriebene Szenario führen die Autoren drei spezifische Tools ein:

1. **`Search[entity]`**: Gibt die ersten fünf Sätze der entsprechenden Wikipedia-Seite zurück (falls existent) oder die fünf ähnlichsten Entitäten basierend auf einer Wikipedia-Suche.
2. **`Lookup[string]`**: Durchsucht die zuletzt abgerufene Entität (aus `Search`) und gibt den nächsten Satz zurück, der den angegebenen String enthält.
3. **`Finish[answer]`**: Signalisiert, dass die Arbeit abgeschlossen ist, und gibt die finale Antwort an.

**Beispiel aus dem Buch (HotpotQA-Datensatz):**
Die Frage lautet: _„Which magazine was started first, Arthur’s Magazine or First for Women?“_  
Ein Mensch würde beide Magazine nachschlagen, die Gründungsdaten vergleichen und dann die Antwort geben. Das Modell führt dies wie folgt aus:

- **Thought 1:** I need to search Arthur’s Magazine and First for Women and find which was started first.
- **Action 1:** `Search[Arthur’s Magazine]`
- **Observation 1:** Arthur’s Magazine (1844-1846) was an American literary periodical published in Philadelphia in the 19th century.
- **Thought 2:** Arthur’s Magazine was started in 1844. I need to search First for Women next.
- **Action 2:** `Search[First for Women]`
- **Observation 2:** First for Women is a women’s magazine published by Bauer Media Group in the USA. [...] The magazine was started in 1989.
- **Thought 3:** First for Women was started in 1989. 1844 (Arthur’s Magazine) < 1989 (First for Women), so Arthur’s Magazine was started first.
- **Action 3:** `Finish[Arthur’s Magazine]`

### Prompting-Strategie

Um das Modell zur Nutzung dieses Musters zu konditionieren, injizieren die Autoren ein spezifisches Präambel in den Prompt, gefolgt von sechs Few-Shot-Beispielen, die das Think-Act-Observe-Muster demonstrieren, und erst danach die eigentliche Frage. Das Präambel lautet sinngemäß:
_„Solve a question-answering task with interleaving Thought, Action, and Observation steps. Thought can reason about the current situation, and Action can be three types: (1) Search[entity]... (2) Lookup[keyword]... (3) Finish[answer]...“_

### Performance und Fine-Tuning

- **Anfängliche Probleme:** Ohne weiteres Training performte ReAct auf dem HotpotQA-Datensatz zunächst schlechter als sowohl Standard-Prompting als auch Chain-of-Thought-Prompting. Die Few-Shot-Beispiele im Prompt allein reichten nicht aus, um dem Modell die Funktionsweise der Tools und das Reasoning beizubringen.
- **Durchbruch durch Fine-Tuning:** Nachdem die kleineren Modelle mit nur 3.000 Beispielen fine-getunt wurden, schoss die Leistung von ReAct an die Spitze.
  - Ein fine-getuntes 8B-Modell mit ReAct übertraf Standard-Prompting-Ansätze auf einem ursprünglichen 62B-Modell.
  - Ein fine-getuntes 62B-Modell mit ReAct übertraf andere Prompting-Ansätze auf einem ursprünglichen 540B-Modell.
- **Die Rolle des Reasonings:** Ein Teil des Erfolgs bei HotpotQA liegt daran, dass ReAct Suchtools nutzen kann, um fehlende Fakten nachzuschlagen. Wenn man den Reasoning-Schritt weglässt und nur handelt (genannt **„Act“**), ist die Leistung immer noch ziemlich gut, da das Modell einfach Fakten abruft.

### Reasoning in komplexen Entscheidungsaufgaben (ALFWorld)

Bei Entscheidungsaufgaben wie dem **ALFWorld**-Benchmark wird das Reasoning kritisch. Hierbei muss das Modell als Agent agieren, der in einem simulierten Haus navigiert und Aufgaben erledigt (ähnlich alten textbasierten Rollenspielen).

In dieser Domäne listet das Paper mehrere Merkmale des „Denkens“ auf, die zu höheren Erfolgsraten führen:

1. Zerlegen von Aufgabenzielen und Erstellen von Aktionsplänen (Decomposing task goals and creating plans of action).
2. Einspeisen von Common-Sense-Wissen, das für die Lösung der Aufgabe relevant ist (Injecting commonsense knowledge).
   is relevant to solving the task).
3. Extrahieren hilfreicher Details aus den Beobachtungen (Extracting helpful details from observations).
4. Verfolgen des Fortschritts und Vorantreiben der Aktionspläne (Tracking progress and pushing action plans forward).
5. Behandeln von Ausnahmen und Anpassen des Aktionsverlaufs (Handling exceptions and adjusting the course of action).

Im Vergleich dazu ist reines Handeln ohne Denken (**Act**) schlechter darin, Ziele in Teilziele zu zerlegen, und neigt dazu, den Zustand der Umgebung aus den Augen zu verlieren.

- **Ergebnis:** ReAct erreicht eine Erfolgsquote von **71 %** bei ALFWorld-Aufgaben, während reines Act nur auf **45 %** kommt.

### Fazit des Abschnitts

**ReAct** kombiniert Reasoning mit der aktiven Nutzung externer Tools in einem iterativen **Think-Act-Observe-Loop**. Während reine Few-Shot-Beispiele im Prompt oft nicht ausreichen, führt ein gezieltes Fine-Tuning mit ReAct-Daten zu massiven Leistungssteigerungen, die sogar größere, nicht fine-getunte Modelle übertreffen. In komplexen, mehrstufigen Entscheidungsumgebungen (wie ALFWorld) ist der Reasoning-Schritt unverzichtbar, da er dem Modell erlaubt, Ziele zu zerlegen, den Kontext zu verfolgen und auf Fehler zu reagieren, was die Erfolgsquote im Vergleich zu reinem Handeln (Act) drastisch erhöht.

## Beyond ReAct

Obwohl **ReAct** ein sehr wichtiger Schritt zur Verbesserung der Reasoning-Fähigkeiten in LLM-Anwendungen war, ist es nicht die letzte denkbare Verbesserung. Dieser Abschnitt stellt drei verwandte Ansätze vor, die vielversprechende Ergebnisse zeigen, um das Reasoning weiter zu optimieren.

### 1. Plan-and-Solve Prompting

Während ReAct direkt in den Think-Act-Observe-Loop einsteigt, fordert der **Plan-and-Solve**-Ansatz das Modell auf, zunächst einen übergeordneten Plan zu entwerfen.

- **Prompt-Beispiel:** „Let’s first understand the problem and devise a plan to solve the problem. Then, let’s carry out the plan and solve the problem step-by-step.“
- **Fokus:** Im Gegensatz zu ReAct beinhaltet dieser Ansatz keine Tool-Nutzung. Er konzentriert sich rein darauf, das Reasoning zu verbessern, ohne externe Daten abzurufen. Er ist damit eng mit dem Chain-of-Thought-Ansatz („Let’s think step-by-step“) verwandt.
- **Kernidee:** Das Modell kann in bestimmten Domänen bessere Leistungen erbringen, wenn man es auffordert, das Problem ganzheitlich zu verstehen und einen Plan zu erstellen, bevor es direkt in die schrittweise Problemlösung springt. Die Kombination dieses Preplanning-Ansatzes mit den Think-Act-Observe-Schritten von ReAct könnte zu weiteren Verbesserungen führen.

### 2. Reflexion

Wenn Plan-and-Solve das ReAct-Modell durch vorausschauende Planung ergänzt, macht **Reflexion** (eingeführt im 2023er Paper „Reflexion: Language Agents with Verbal Reinforcement Learning“) das Gegenteil: Es erlaubt dem Modell, seine Arbeit _nachträglich_ zu überprüfen, Probleme zu identifizieren und beim nächsten Mal bessere Pläne zu schmieden.

- **Einschränkung:** Dieser Ansatz hilft wenig, wenn der gemachte Fehler nicht rückgängig gemacht werden kann.
  - _Beispiel aus dem Buch (nicht rückgängig machbar):_ „I’m sorry for transferring your assets to your ex-husband’s account. I won’t do that again!“
- **Anwendungsfall:** Es gibt viele Domänen, in denen man einen „Do-over“ (zweiten Versuch) hat.
  - _Beispiel aus dem Buch:_ Das Schreiben von Software, die eine Suite von Unit-Tests besteht. Mit Reflexion kann man Softwareteile erstellen (z. B. mit ReAct). Wenn die Unit-Tests am Ende nicht bestehen, werden die Fehlermeldungen in den Prompt eingefügt, sodass das Modell es erneut versuchen und diesmal dieselben Fehler vermeiden kann.

### 3. Branch-Solve-Merge

Dieser Ansatz lässt sich bereits aus seinem Namen ableiten und folgt einem mehrstufigen Prozess:

1.  Bei einem gegebenen Problem verzweigt man zu $N$ verschiedenen Solvern (unabhängige LLM-Konversationen), von denen jeder das Problem isoliert angeht.
2.  Man kann sie einfach drei unabhängige Versuche unternehmen lassen (wobei man eine relativ hohe Temperatur verwendet, um sicherzustellen, dass ihre Lösungstechniken unterschiedlich sind).
3.  Besser ist es, jeden Solver per Prompt anzuweisen, das Problem aus einer _anderen Perspektive_ anzugehen.
4.  Sobald alle Solver fertig sind, werden die von ihnen produzierten Inhalte kombiniert und einem **Merging Agent** vorgelegt. Dieser kombiniert die Informationen aller Solver zu einer besseren oder vollständigeren Lösung.

### Konvergierende Ideen

Der Abschnitt schließt mit der Beobachtung, dass sich die Ideen in diesem Kapitel überschneiden: Es werden die im ersten Teil des Kapitels eingeführten Tools mit neuen Techniken kombiniert, die die Reasoning-Fähigkeiten des Modells verbessern. In allen diesen Fällen geschieht dies, indem man dem Modell einen eigenen **internal monologue** gibt, damit es die Situation verarbeiten, Ziele herunterbrechen und bessere Entscheidungen darüber treffen kann, wie eine Aufgabe zu erledigen ist. Damit sind fast alle Zutaten für den Bau eigener autonomer Agenten vorhanden – bis auf eine letzte: den Kontext.

### Fazit des Abschnitts

Über **ReAct** hinaus gibt es drei vielversprechende Ansätze zur Steigerung der Reasoning-Leistung: **Plan-and-Solve Prompting** (vorausschauende Planung vor der Ausführung), **Reflexion** (nachträgliche Überprüfung und Korrektur bei wiederholbaren Aufgaben wie Unit-Tests) und **Branch-Solve-Merge** (paralleles Lösen aus verschiedenen Perspektiven mit anschließender Zusammenführung). Allen gemeinsam ist das Ziel, dem Modell durch einen simulierten _internal monologue_ die Fähigkeit zu geben, Probleme ganzheitlicher zu verarbeiten und fundiertere Entscheidungen zu treffen, was die Grundlage für fortgeschrittene autonome Agenten bildet.
