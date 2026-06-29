# Kapital 9

## Playing by the rules

Dieser einleitende Abschnitt führt das zentrale Thema des Kapitels ein: **business rules** (auch **business logic** genannt) und ihre Beziehung zu Software Requirements.

### Einstiegsbeispiel (Vignette aus dem Buch)

**Beispiel aus dem Buch:** Jackie ruft Tim an, den **product champion** des Chemical Tracking System. Sie kann über das System kein weiteres **phosgene** (für Farbstoffe in ihrem Forschungsprojekt) anfordern. Das System verweigert die Anfrage mit dem Hinweis, sie habe seit über einem Jahr keine Schulung im Umgang mit hazardous chemicals besucht. Tim erklärt: Contoso verlangt eine jährliche Auffrischungsschulung im sicheren Umgang mit Gefahrstoffen. Dies ist eine **corporate policy**, die auf **OSHA regulations** basiert. Das Chemical Tracking System setzt diese Regel lediglich durch ("enforces it"). Früher gaben die "stockroom guys" das Material einfach heraus – das ist jetzt nicht mehr erlaubt. Jackie muss erst die Auffrischungsschulung absolvieren.

Dieses Beispiel zeigt: Eine Regel, die außerhalb der Software existiert (Unternehmensrichtlinie/Gesetz), wird durch die Software durchgesetzt.

### Was sind business rules?

1. Jede Organisation operiert nach einer umfangreichen Menge an **policies, laws und industry standards**.
2. Bestimmte Branchen müssen besonders viele staatliche Regulierungen einhalten. **Beispiel aus dem Buch:** banking, aviation und medical device manufacture müssen Volumina an government regulations befolgen.
3. Diese steuernden Prinzipien werden gemeinsam als **business rules** oder **business logic** bezeichnet.
4. Business rules werden oft durch **manuelle Umsetzung** von Policies und Prozeduren durchgesetzt – in vielen Fällen müssen sie aber auch durch Softwareanwendungen erzwungen werden.

### Business rules sind keine Requirements – aber eine Quelle dafür

1. Die meisten business rules entstehen **außerhalb des Kontexts einer spezifischen Softwareanwendung**.
2. **Beispiele aus dem Buch:** Die corporate policy zur jährlichen Gefahrstoff-Schulung gilt auch dann, wenn der gesamte Chemikalieneinkauf manuell abgewickelt würde. Standard accounting practices existierten lange vor der Erfindung des digitalen Computers.
3. Weil business rules eine **Eigenschaft des Business** sind, sind sie **selbst keine software requirements**.
4. Sie sind jedoch eine **reiche Quelle von Requirements**, weil sie Eigenschaften diktieren, die das System besitzen muss, um den Regeln zu entsprechen.
5. Wie Figure 1-1 (aus Kapitel 1) zeigt, können business rules der **Ursprung mehrerer Arten von Requirements** sein. Table 9-1 illustriert, wie business rules verschiedene Requirement-Typen beeinflussen.

#### Figure 1-1 (als Markdown nachgebildet)

*Relationships among several types of requirements information. Solid arrows mean "are stored in"; dotted arrows mean "are the origin of" or "influence".*

Beziehungen (solid = "are stored in", dotted = "are the origin of"/"influence"):

| Von | Pfeil-Typ | Nach |
|---|---|---|
| Business Requirements | solid (are stored in) | Vision and Scope Document |
| Business Rules | dotted (origin of / influence) | Business Requirements |
| Business Rules | dotted | User Requirements |
| Business Rules | dotted | Functional Requirements |
| Business Rules | dotted | Quality Attributes |
| Vision and Scope Document | dotted | User Requirements |
| User Requirements | solid (are stored in) | User Requirements Document |
| User Requirements Document | dotted | Functional Requirements |
| System Requirements | dotted | Functional Requirements |
| Quality Attributes | dotted | Functional Requirements |
| Functional Requirements | solid (are stored in) | Software Requirements Specification |
| External Interfaces | solid | Software Requirements Specification |
| Quality Attributes | solid | Software Requirements Specification |
| Constraints | solid | Software Requirements Specification |

Kernaussage der Figur: **Business Rules** stehen oben und "speisen" (als origin/influence) Business Requirements, User Requirements, Functional Requirements und Quality Attributes. Die verschiedenen Requirement-Typen werden letztlich in Dokumenten gespeichert (Vision and Scope Document, User Requirements Document, Software Requirements Specification).

#### Table 9-1 (als Markdown nachgebildet)

*How business rules can influence various types of software requirements*

| Requirement type | Illustration of business rules' influence | Example (aus dem Buch) |
|---|---|---|
| **Business requirement** | Government regulations können zu notwendigen business objectives für ein Projekt führen. | *The Chemical Tracking System must enable compliance with all federal and state chemical usage and disposal reporting regulations within five months.* |
| **User requirement** | Privacy policies bestimmen, welche Nutzer bestimmte Aufgaben mit dem System ausführen dürfen und welche nicht. | *Only laboratory managers are allowed to generate chemical exposure reports for anyone other than themselves.* |
| **Functional requirement** | Company policy verlangt, dass alle Vendors registriert und genehmigt sein müssen, bevor eine Rechnung bezahlt wird. | *If an invoice is received from an unregistered vendor, the Supplier System shall email the vendor editable PDF versions of the supplier intake form and the W-9 form.* |
| **Quality attribute** | Regulierungen von Behörden wie OSHA und EPA können safety requirements diktieren, die durch Systemfunktionalität durchgesetzt werden müssen. | *The system must maintain safety training records, which it must check to ensure that users are properly trained before they can request a hazardous chemical.* |

### Abgrenzung: business rules vs. business processes vs. business requirements

Laut Autor werden business rules manchmal mit business processes oder business requirements verwechselt:

1. Ein **business requirement** beschreibt ein wünschenswertes Ergebnis oder ein high-level objective der Organisation, die eine Softwarelösung baut oder beschafft. Es dient als **Rechtfertigung (justification)** dafür, ein Projekt überhaupt zu starten.
2. Ein **business process** beschreibt eine Reihe von Aktivitäten, die Inputs in Outputs transformieren, um ein bestimmtes Ergebnis zu erreichen. Informationssysteme automatisieren häufig business processes, was zu Effizienzgewinnen und anderen Vorteilen führen kann, die die business requirements erfüllen.
3. **Business rules beeinflussen business processes**, indem sie:
    - Vokabular etablieren (establishing vocabulary),
    - Restriktionen auferlegen (imposing restrictions),
    - Aktionen auslösen (triggering actions),
    - regeln, wie Berechnungen durchgeführt werden (governing how computations are carried out).
4. Dieselbe business rule kann auf **mehrere manuelle oder automatisierte Prozesse** zutreffen. Genau deshalb ist es am besten, business rules als eine **separate Menge von Informationen** zu behandeln.

### Dokumentation und Verwaltung von business rules

1. Nicht alle Firmen behandeln ihre essenziellen business rules als das wertvolle **enterprise asset**, das sie sind.
2. Manche Abteilungen dokumentieren ihre lokalen Regeln, aber vielen Firmen fehlt ein **unified effort**, business rules in einem gemeinsamen, für die IT-Organisation zugänglichen **common repository** zu dokumentieren.
3. Werden diese Informationen wie "**corporate folklore**" behandelt, entstehen zahlreiche Probleme:
    - Nicht dokumentierte/verwaltete Regeln existieren nur in den Köpfen einzelner Personen.
    - Ein **BA (Business Analyst)** muss wissen, wen er kontaktieren muss, um etwas über die für sein Projekt relevanten Regeln zu erfahren.
    - Einzelne Personen können **widersprüchliche Auffassungen** der Regeln haben. Folge: Verschiedene Anwendungen setzen dieselbe business rule inkonsistent durch oder übersehen sie ganz.
4. Ein **master repository** von business rules erleichtert es allen betroffenen Projekten, die Regeln kennenzulernen und **konsistent** zu implementieren.

> **Trap (Warnung aus dem Buch):** Undokumentierte business rules, die nur bestimmten Experten bekannt sind, führen zu einem **knowledge vacuum**, sobald diese Experten die Organisation verlassen.

### Beispiel: security policies

**Beispiel aus dem Buch:** Eine Organisation hat typischerweise security policies, die den Zugriff auf Informationssysteme steuern. Solche Policies können regeln:

1. minimale und maximale Länge sowie erlaubte Zeichen in Passwörtern,
2. die Häufigkeit erforderlicher Passwortänderungen,
3. wie viele fehlgeschlagene Login-Versuche ein Nutzer hat, bevor sein Account gesperrt wird.

Wichtige Punkte dazu:
- Anwendungen, die die Organisation entwickelt, sollten diese Policies (= diese business rules) **konsistent anwenden**.
- Das **Tracing** jeder Regel bis in den implementierenden Code macht es einfacher, Systeme bei Regeländerungen zu aktualisieren (z. B. Änderung der erforderlichen Häufigkeit von Passwortänderungen).
- Es erleichtert außerdem **code reuse** über Projekte hinweg.

### Fazit des Abschnitts

Business rules (business logic) sind die policies, laws und standards einer Organisation. Sie existieren unabhängig von Software und sind **selbst keine Requirements**, dienen aber als zentrale **Quelle** für Business Requirements, User Requirements, Functional Requirements und Quality Attributes (siehe Figure 1-1 und Table 9-1). Sie müssen klar von business requirements (Projekt-Rechtfertigung) und business processes (Aktivitätenfolgen) abgegrenzt werden. Weil dieselbe Regel viele Prozesse und Anwendungen betrifft, sollte sie als **separate, zentral dokumentierte Information** (master repository) behandelt werden – andernfalls droht inkonsistente Umsetzung und ein knowledge vacuum beim Ausscheiden von Experten.

## A business rules taxonomy

Die **Business Rules Group (2012)** liefert Definitionen für business rules aus zwei Perspektiven:

1. **From the business perspective:** Eine business rule ist eine Anleitung (guidance), dass eine Verpflichtung bezüglich Verhalten, Aktion, Praxis oder Prozedur innerhalb einer bestimmten Aktivität oder Sphäre besteht. Es sollte eine explizite Motivation für die Regel geben, ebenso Durchsetzungsmethoden und ein Verständnis der Konsequenzen, falls die Regel gebrochen wird.

2. **From the information system perspective:** Eine business rule ist ein Statement, das einen Aspekt des Business definiert oder einschränkt (constrains). Es soll Business-Struktur festlegen oder das Verhalten des Business steuern bzw. beeinflussen.

Es wurden ganze Methodologien entwickelt, die auf der Entdeckung und Dokumentation von business rules und deren Implementierung in automatisierten business rules systems basieren (von Halle 2002; Ross 1997; Ross und Lam 2011). Solange man jedoch kein stark rules-driven System baut, braucht man keine aufwendige Methodologie. Es genügt, die für das System relevanten Regeln zu identifizieren, zu dokumentieren und mit den konkreten Requirements zu verknüpfen, die sie implementieren.

**Die einfache Taxonomie (Figure 9-1):** Die simple Taxonomie funktioniert für die meisten Situationen und gliedert business rules in fünf Typen:

```
                       Business Rules
   ┌───────┬─────────────┬───────────────┬────────────┬──────────────┐
 Facts  Constraints  Action Enablers   Inferences   Computations
```

Zusätzlich gibt es eine sechste Kategorie: **terms** — definierte Wörter, Phrasen und Abkürzungen, die für das Business wichtig sind. Man kann terms zu den factual business rules gruppieren; ein Glossary ist ein weiterer praktischer Ort, um terms zu definieren.

Wichtige Hinweise des Autors:

1. **Konsistente Aufzeichnung wichtiger als perfekte Klassifikation.** Business rules konsistent zu erfassen ist wichtiger als hitzige Diskussionen darüber, wie genau jede einzelne klassifiziert wird.
2. **Taxonomie als Denkhilfe.** Eine Taxonomie hilft, business rules zu identifizieren, an die man sonst nicht gedacht hätte.
3. **Klassifikation deutet auf Anwendung im System hin.** Zum Beispiel führen **constraints** oft zu Systemfunktionalität, die Restriktionen durchsetzt, und **action enablers** führen zu Funktionalität, die unter bestimmten Bedingungen etwas auslöst.

### Fazit des Abschnitts
Business rules lassen sich aus Business- und Information-System-Perspektive definieren (Business Rules Group 2012). Statt aufwendiger Methodologien reicht es meist, die relevanten Regeln zu dokumentieren und an Requirements zu binden. Die einfache Taxonomie (Figure 9-1) unterscheidet fünf Typen — Facts, Constraints, Action Enablers, Inferences, Computations — plus optional terms. Konsistente Erfassung ist wichtiger als perfekte Einordnung, aber die Klassifikation hilft beim Finden und beim Umsetzen der Regeln.

## Facts

**Facts** sind schlicht Aussagen, die zu einem bestimmten Zeitpunkt über das Business wahr sind. Ein fact beschreibt Assoziationen oder Beziehungen (relationships) zwischen wichtigen business terms. Facts über data entities, die für das System wichtig sind, können in data models auftauchen.

**Beispiele aus dem Buch:**
1. Jeder chemical container hat einen eindeutigen bar code identifier.
2. Jede order hat einen shipping charge.
3. Sales tax wird nicht auf shipping charges berechnet.
4. Nonrefundable airline tickets verursachen eine Gebühr, wenn der Käufer die itinerary ändert.
5. Bücher höher als 16 inches werden in der Oversize section der Bibliothek einsortiert.

**Warnung des Autors:** Es gibt unzählige facts über Businesses. Das Sammeln irrelevanter facts kann die Business-Analyse lähmen. Auch wenn ein fact wahr ist, ist nicht immer offensichtlich, wie das Entwicklungsteam die Information nutzen soll. Man sollte sich auf facts konzentrieren, die im Scope des Projekts liegen, statt eine vollständige Sammlung von Business-Wissen anzuhäufen. Jeder fact sollte mit den Inputs/Outputs des context diagram, mit system events, mit bekannten data objects oder mit konkreten user requirements verbunden werden.

### Fazit des Abschnitts
Facts sind wahre Aussagen über Beziehungen zwischen business terms und tauchen oft in data models auf. Entscheidend ist Fokussierung: Nur projektrelevante facts erfassen und jeweils mit konkreten Requirements, Datenobjekten oder System-Events verknüpfen, statt irrelevantes Business-Wissen anzusammeln.

## Constraints

Ein **constraint** ist ein Statement, das die Aktionen einschränkt, die das System oder seine User durchführen dürfen. Wer eine constraining business rule beschreibt, sagt typischerweise, dass bestimmte Aktionen durchgeführt werden *müssen*, *nicht müssen* oder *nicht dürfen*, oder dass nur bestimmte Personen oder Rollen bestimmte Aktionen ausführen dürfen.

**Beispiele aus dem Buch nach Herkunft gegliedert:**

**Organizational policies:**
1. Ein loan applicant unter 18 Jahren muss einen Elternteil oder legal guardian als Mitunterzeichner (cosigner) des Kredits haben.
2. Ein library patron darf maximal 10 items gleichzeitig auf hold haben.
3. Versicherungskorrespondenz darf nicht mehr als vier Ziffern der Social Security number des Versicherten anzeigen.

**Government regulations:**
1. Alle Software-Applikationen müssen Regularien zur Nutzung durch sehbehinderte Personen erfüllen.
2. Airline-Piloten müssen in jedem 24-Stunden-Zeitraum mindestens 8 zusammenhängende Stunden Ruhe erhalten.
3. Individuelle federal income tax returns müssen bis Mitternacht am ersten Werktag nach dem 14. April abgestempelt sein, sofern keine Fristverlängerung gewährt wurde.

**Industry standards:**
1. Mortgage loan applicants müssen die Qualifikationsstandards der Federal Housing Authority erfüllen.
2. Web applications dürfen keine HTML tags oder attributes enthalten, die laut HTML 5 standard deprecated sind.

**Sidebar „So many constraints" (Abgrenzung verschiedener Constraint-Arten):**
1. **Project-level constraints** (Schedule, Staff, Budget) gehören in den project management plan.
2. **Product design and implementation constraints** (auferlegte Bedingungen, die man sonst den Entwicklern überlassen würde) gehören in die SRS oder design specification.
3. **Business rules constraints** (Einschränkungen, wie das Business operiert) gehören in ein business rules repository. Wenn solche constraints sich in den Software-Requirements widerspiegeln, gibt man die zugehörige Regel als rationale für jedes abgeleitete Requirement an.

**Implikationen ohne direkte Funktionalität — Beispiel aus dem Buch (Retail Store):** Eine Richtlinie besagt, dass nur supervisors und managers cash refunds über 50 \$ ausgeben dürfen. Bei einer point-of-sale application impliziert diese Regel, dass jeder User ein privilege level haben muss. Die Software muss prüfen, ob der aktuelle User ein ausreichend hohes privilege level besitzt, um bestimmte Aktionen durchzuführen (z. B. die Kassenschublade zu öffnen, damit ein cashier einem Kunden eine Rückerstattung gibt). → Constraining business rules können also Implikationen für die Entwicklung haben, selbst wenn sie nicht direkt in Funktionalität übersetzt werden.

**Roles and permissions matrix (Figure 9-2):** Da viele constraint-Regeln damit zu tun haben, welche User-Typen welche Funktionen ausführen dürfen, ist eine **roles and permissions matrix** (Beatty und Chen 2012) eine kompakte Dokumentationsform. Figure 9-2 zeigt eine solche Matrix für ein Bibliotheks-Informationssystem:

- **Rollen** sind in employees (Administrator, Circulation Staff, Library Aide) und non-employees (Volunteer, Patron) getrennt.
- **Funktionen** sind gruppiert in System Operations, Patron Records und Item Operations.
- Ein **X** in einer Zelle bedeutet, dass die in der Spalte genannte Rolle die in der Zeile gezeigte Operation ausführen darf.

Inhalt der Matrix (X = Berechtigung):

| Operation | Administrator | Circulation Staff | Library Aide | Volunteer | Patron |
|---|:---:|:---:|:---:|:---:|:---:|
| **System Operations** | | | | | |
| Log in to library system | X | X | X | | |
| Set up new staff members | X | | | | |
| Print hold pick list | X | X | X | | |
| **Patron Records** | | | | | |
| View a patron record | X | X | | | |
| Edit a patron record | X | X | | | |
| View your own patron record | X | X | X | X | X |
| Issue a library card | X | X | | | |
| Accept a fine payment | X | X | | | |
| **Item Operations** | | | | | |
| Search the library catalog | X | X | X | X | X |
| Check out an item | X | X | | | |
| Check in an item | X | X | X | X | |
| Route an item to another branch | X | X | X | X | |
| Put an item on hold | X | X | X | X | X |

### Fazit des Abschnitts
Constraints schränken erlaubte Aktionen von System oder Usern ein und entstehen aus organizational policies, government regulations oder industry standards. Wichtig ist die Unterscheidung von project-, design- und business-rule-constraints, die in unterschiedliche Dokumente gehören. Constraints können Entwicklungsimplikationen haben (z. B. privilege levels), auch ohne direkte Funktionalität. Da sie oft Rollen mit Berechtigungen verknüpfen, ist die roles and permissions matrix (Figure 9-2) eine kompakte Dokumentationsform.

## Action enablers

Eine Regel, die eine Aktivität auslöst (triggert), wenn bestimmte Bedingungen wahr sind, ist ein **action enabler**. Die Aktivität könnte von einer Person in einem manuellen Prozess durchgeführt werden, oder die Regel führt zur Spezifikation von Software-Funktionalität, die das korrekte Verhalten zeigt, sobald das System das auslösende Event erkennt. Die Bedingungen können eine komplexe Kombination wahrer/falscher Werte mehrerer Einzelbedingungen sein. Eine **decision table** bietet eine kompakte Möglichkeit, action-enabling business rules mit umfangreicher Logik zu dokumentieren.

**Erkennungsmuster:** Ein Statement der Form „If `<some condition is true or some event takes place>`, then `<something happens>`" deutet auf einen action enabler hin.

**Beispiele aus dem Buch (Chemical Tracking System):**
1. Wenn der chemical stockroom Container einer angeforderten Chemikalie auf Lager hat, dann biete dem Anforderer die vorhandenen Container an.
2. Am letzten Tag eines Kalenderquartals generiere die vorgeschriebenen OSHA- und EPA-Reports zur Handhabung und Entsorgung von Chemikalien für dieses Quartal.
3. Wenn das Verfallsdatum eines chemical container erreicht ist, benachrichtige die Person, die diesen Container aktuell besitzt.

**Beispiele aus dem Buch (Online-Buchhandlung, zur Stimulierung von Impulskäufen):**
1. Wenn der Kunde ein Buch eines Autors bestellt hat, der mehrere Bücher geschrieben hat, dann biete vor Abschluss der Bestellung die anderen Bücher des Autors an.
2. Nachdem ein Kunde ein Buch in den shopping cart gelegt hat, zeige verwandte Bücher an, die andere Kunden ebenfalls gekauft haben.

**Sidebar „Overruled by constraints" — Beispiel aus dem Buch (Blue Yonder Airlines):** Der Autor wollte mit frequent-flyer miles ein Ticket für seine Frau (anderer Nachname) kaufen. BlueYonder.com brach mit einer Fehlermeldung ab und verlangte einen Anruf. Der Grund war eine constraining business rule, sinngemäß: „Wenn der Passagier einen anderen Nachnamen als der mileage redeemer hat, dann muss der redeemer das Ticket persönlich abholen" — vermutlich zur Betrugsprävention. Die Software setzte die Regel durch, aber so schlecht, dass sie zu usability-Mängeln und Kundenunannehmlichkeiten führte (alarmierende Fehlermeldung statt klarer Erklärung, unnötiger Anruf). **Lehre:** Schlecht durchdachte Implementierungen von business rules können den Kunden und damit das Business negativ beeinflussen.

### Fazit des Abschnitts
Action enablers triggern eine Aktivität, wenn bestimmte Bedingungen erfüllt sind, erkennbar am „If…then…"-Muster; bei komplexer Logik hilft eine decision table. Sie können sowohl regulatorische Pflichten (OSHA/EPA-Reports) als auch kommerzielle Ziele (Impulskäufe) abbilden. Das Blue-Yonder-Beispiel warnt: Eine schlecht umgesetzte (constraint-getriebene) Regel kann die Usability und damit die Kundenbeziehung beschädigen.

## Inferences

Eine **inference** (auch *inferred knowledge* oder *derived fact*) erzeugt einen neuen fact aus anderen facts. Inferences werden oft im „if/then"-Muster geschrieben, das auch bei action enablers vorkommt — der Unterschied ist jedoch: Die „then"-Klausel einer inference liefert lediglich ein Stück Wissen, keine auszuführende Aktion.

**Beispiele aus dem Buch:**
1. Wenn eine Zahlung nicht innerhalb von 30 Kalendertagen nach Fälligkeit eingeht, dann ist das Konto delinquent.
2. Wenn der Vendor ein bestelltes item nicht innerhalb von fünf Tagen nach Auftragseingang versenden kann, dann gilt das item als back-ordered.
3. Chemikalien mit einer LD50-Toxizität unter 5 mg/kg bei Mäusen gelten als hazardous.

### Fazit des Abschnitts
Inferences leiten aus vorhandenen facts neues Wissen ab. Sie ähneln syntaktisch den action enablers („if/then"), unterscheiden sich aber darin, dass die „then"-Klausel ein neues Faktum statt einer Aktion liefert.

## Computations

Die fünfte Klasse von business rules definiert **computations**, die existierende Daten mithilfe spezifischer mathematischer Formeln oder Algorithmen in neue Daten transformieren. Viele computations folgen Regeln, die außerhalb des Unternehmens liegen, etwa income-tax-withholding-Formeln.

**Beispiele aus dem Buch (in Textform):**
1. Der domestic ground shipping charge für eine Bestellung über zwei Pfund beträgt 4,75 \$ plus 12 Cent pro Unze oder Bruchteil davon.
2. Der Gesamtpreis einer Bestellung ist die Summe der Preise der bestellten items, abzüglich etwaiger volume discounts, plus state- und county-sales-taxes für den Lieferort, plus shipping charge, plus optionalem insurance charge.
3. Der Stückpreis wird um 10 % reduziert bei Bestellungen von 6–10 units, um 20 % bei 11–20 units und um 30 % bei mehr als 20 units.

**Klarere Darstellung statt Fließtext:** Computations in natürlicher Sprache zu beschreiben kann wortreich und verwirrend sein. Als Alternative kann man sie in symbolischer Form darstellen — als mathematischen Ausdruck oder als Regeltabelle, die klarer und leichter zu pflegen ist.

**Table 9-2 (Using a table to represent computational business rules)** stellt die Rabattregel klarer dar:

| ID | Number of units purchased | Percent discount |
|---|---|---|
| DISC-1 | 1 through 5 | 0 |
| DISC-2 | 6 through 10 | 10 |
| DISC-3 | 11 through 20 | 20 |
| DISC-4 | More than 20 | 30 |

> **Trap (Warnung aus dem Buch):** Achte auf **boundary value overlaps**, wenn du business rules oder Requirements mit Wertebereichen schreibst. Es ist leicht, versehentlich Bereiche wie 1–5, 5–10 und 10–20 zu definieren, was Mehrdeutigkeit darüber erzeugt, in welchen Bereich die Werte genau 5 und genau 10 fallen.

### Fazit des Abschnitts
Computations transformieren vorhandene Daten per Formel oder Algorithmus in neue Daten und stammen oft aus externen Quellen (z. B. Steuerformeln). Statt verwirrendem Fließtext sollte man sie symbolisch oder als Regeltabelle (Table 9-2) dokumentieren. Beim Definieren von Wertebereichen ist auf boundary value overlaps zu achten, um Mehrdeutigkeit an den Bereichsgrenzen zu vermeiden.

## Atomic business rules

Wenn man eine Bibliothekarin fragt, wie lange man eine DVD ausleihen kann, könnte sie antworten: Man kann eine DVD oder Blu-ray Disc für eine Woche ausleihen und sie bis zu zweimal um je drei Tage verlängern, aber nur, wenn kein anderer patron einen hold darauf platziert hat. Diese Antwort basiert auf den business rules der Bibliothek, kombiniert aber mehrere Regeln zu einem einzigen Statement.

Probleme mit solchen **composite business rules**:

1. **Schwer zu verstehen und zu pflegen.** Zusammengesetzte Regeln sind schwerer nachzuvollziehen und zu warten.
2. **Schwer auf Vollständigkeit zu prüfen.** Es ist schwierig zu bestätigen, dass alle möglichen Bedingungen abgedeckt sind.
3. **Aufwendige Codeänderungen.** Wenn mehrere Funktionalitätssegmente auf eine komplexe Regel zurückverweisen (trace back), kann es zeitaufwendig sein, den passenden Code zu finden und zu ändern, sobald sich künftig nur *ein Teil* der Regel ändert.

**Die bessere Strategie — atomic level:** Business rules sollten auf dem **atomic level** geschrieben werden, statt mehrere Details in einer Regel zu kombinieren. Vorteile:

1. Hält die Regeln kurz und einfach (short and simple).
2. Erleichtert das Wiederverwenden (reuse) der Regeln.
3. Erleichtert das Modifizieren der Regeln.
4. Erleichtert das Kombinieren der Regeln auf verschiedene Weise.

**Regeln zum atomaren Schreiben:** Um inferred knowledge und action-enabling business rules atomar zu schreiben:
1. Verwende keine **„or"-Logik** auf der linken Seite (left-hand side) einer „if/then"-Konstruktion.
2. Vermeide **„and"-Logik** auf der rechten Seite (right-hand side).

**Beispiel aus dem Buch (Table 9-3 – Zerlegung der komplexen Bibliotheksregel in atomare Regeln):**

| ID | Rule |
|---|---|
| Video.Media.Types | DVD discs und Blu-ray Discs sind video items. |
| Video.Checkout.Duration | Video items dürfen jeweils für eine Woche ausgeliehen werden. |
| Renewal.Video.Times | Video items dürfen bis zu zweimal verlängert (renewed) werden. |
| Renewal.Video.Duration | Das Verlängern eines ausgeliehenen video item verlängert das due date um drei Tage. |
| Renewal.HeldItem | Ein patron darf ein item nicht verlängern, das ein anderer patron auf hold hat. |


Diese Regeln heißen **atomic**, weil sie sich nicht weiter zerlegen lassen (can't be decomposed further). Man endet wahrscheinlich mit vielen atomic business rules, und die functional requirements hängen von verschiedenen Kombinationen dieser Regeln ab.

**Wartungsvorteil (Beispiel aus dem Buch):** Wenn die nächste Generation der Videotechnologie kommt oder die Bibliothek alle ihre DVD discs aussortiert, muss die Bibliothek nur die Regel **Video.Media.Types** aktualisieren — keine der anderen Regeln ist betroffen. Das illustriert, wie atomare Regeln die Wartung erleichtern.

### Fazit des Abschnitts
Composite business rules bündeln mehrere Details in einem Statement und sind dadurch schwer zu verstehen, zu prüfen und zu warten. Besser ist es, Regeln auf atomic level zu schreiben — kurz, einfach, wiederverwendbar und leicht kombinierbar. Praktisch heißt das: kein „or" auf der linken und kein „and" auf der rechten Seite einer „if/then"-Konstruktion. Atomic rules lassen sich nicht weiter zerlegen; functional requirements stützen sich auf deren Kombinationen, und Änderungen bleiben lokal begrenzt (Beispiel Video.Media.Types).

## Documenting Business Rules

Dieser Abschnitt erläutert anhand von **Table 9-4** (ein Beispiel für einen *business rules catalog*), wie man Business Rules dokumentiert und welche Informationen pro Regel erfasst werden sollten.

### Table 9-4 — Some sample business rules catalog entries

Die Tabelle zeigt drei Beispiel-Einträge eines Business-Rules-Katalogs mit fünf Spalten: **ID**, **Rule definition**, **Type of rule**, **Static or dynamic** und **Source**.

| ID | Rule definition | Type of rule | Static or dynamic | Source |
|---|---|---|---|---|
| ORDER-5 | Wenn der Kunde ein Buch eines Autors bestellt, der mehrere Bücher geschrieben hat, sollen ihm vor Abschluss der Bestellung die anderen Bücher dieses Autors angeboten werden. | Action enabler | Static | Marketing policy XX |
| ACCESS-8 | Alle Bilder der Website müssen Alternativtext enthalten, der von elektronischen Lesegeräten genutzt werden kann, um Barrierefreiheits-Anforderungen für sehbehinderte Nutzer zu erfüllen. | Constraint | Static | ADA Standards for Accessible Design |
| DISCOUNT-13 | Ein Rabatt wird basierend auf der Größe der aktuellen Bestellung berechnet, wie in Table BR-060 definiert. | Computation | Dynamic | Corporate pricing policy XX |

*(Alle drei Einträge sind Beispiele aus dem Buch.)*

### Eindeutige Identifier (unique identifier)

1. Jede Business Rule erhält einen **unique identifier**. Damit lassen sich Requirements auf eine bestimmte Regel zurückverlinken.
2. **Beispiel aus dem Buch:** Manche Templates für Use Cases enthalten ein Feld für Business Rules, die den Use Case beeinflussen. Statt die Regeldefinition in die Use-Case-Beschreibung zu schreiben, trägt man dort nur die Identifier der relevanten Regeln ein.
3. Jede ID dient als **Pointer** auf die *master instance* der Business Rule.
4. Vorteil: Die Use-Case-Spezifikation wird nicht obsolet, wenn sich die Regel ändert, weil sie nur auf die Regel verweist und die Definition nicht dupliziert.

### Spalte „Type of rule“

Diese Spalte identifiziert jede Business Rule als einen der folgenden Typen:

1. **Fact**
2. **Constraint**
3. **Action enabler**
4. **Inference**
5. **Computation**

### Spalte „Static or dynamic“

1. Diese Spalte gibt an, wie wahrscheinlich sich eine Regel im Lauf der Zeit ändert.
2. Diese Information ist für **developers** hilfreich: Wenn sie wissen, dass bestimmte Regeln periodisch geändert werden, können sie die Software so strukturieren, dass die betroffene Funktionalität oder die betroffenen Daten leicht aktualisierbar sind.
3. **Beispiel aus dem Buch:** Einkommensteuerberechnungen (income tax calculations) ändern sich mindestens jährlich. Wenn der Entwickler die Steuerinformationen in Tabellen oder einer Datenbank ablegt, statt sie fest in den Code zu schreiben (hard-coding), ist das Aktualisieren deutlich einfacher.
4. **Warnung / Faustregel aus dem Buch:** *Laws of nature* (z. B. Berechnungen nach den Gesetzen der Thermodynamik) können sicher hart codiert werden; *laws of humans* sind dagegen viel volatiler.

#### The laws of separation (Kasten / Sidebar aus dem Buch)

**Beispiel aus dem Buch:** Air-traffic-control-Systeme (ATC) müssen einen Mindestabstand zwischen Flugzeugen in vier Dimensionen sicherstellen — **altitude, lateral, longitudinal und time** —, um Kollisionen zu vermeiden.

1. Bordsysteme, Piloten, Lotsen am Boden und das ATC-System selbst müssen Flugbahn- und Geschwindigkeitsinformationen aus hunderten Quellen zusammenführen, um vorherzusehen, wann sich zwei Flugzeuge gefährlich nahekommen.
2. Viele Business Rules regeln die gesetzlichen Mindestabstände und -zeiten.
3. Diese Regeln sind **dynamic**: Sie ändern sich periodisch, wenn sich Technologie verbessert (Beispiel aus dem Buch: GPS-Positionierung statt Radar) und Vorschriften aktualisiert werden.
4. Konsequenz: Das System muss regelmäßig einen neuen Regelsatz aufnehmen, dessen Selbst-Konsistenz und Vollständigkeit validieren und gleichzeitig mit Piloten und Lotsen auf die neuen Regeln umschalten können.
5. **Warnung aus dem Buch:** Ein ATC-Projekt codierte den aktuellen Regelsatz zunächst hart in die Software ein, weil man die Regeln für *static* hielt. Als die Stakeholder erkannten, dass diese sicherheitskritischen Regeln periodisch geändert werden müssen, war erhebliche **major rework** nötig.

### Spalte „Source“

1. Die letzte Spalte in Table 9-4 identifiziert die **Source** (Quelle) jeder Regel.
2. Mögliche Quellen von Business Rules sind: *corporate and management policies*, *subject matter experts* und andere Personen sowie Dokumente wie *government laws and regulations*.
3. Das Kennen der Quelle hilft den Beteiligten zu wissen, wohin sie sich wenden müssen, wenn sie mehr Informationen über die Regel benötigen oder über Änderungen erfahren wollen.

### Fazit des Abschnitts

Ein Business-Rules-Katalog dokumentiert jede Regel mit einem **unique identifier** (als Pointer auf die Master-Instanz, um Redundanz und Obsoleszenz zu vermeiden), einer **Rule definition**, dem **Type of rule** (fact, constraint, action enabler, inference, computation), der Einordnung **static or dynamic** sowie der **Source**. Besonders die Unterscheidung static/dynamic ist für Entwickler wichtig: Dynamische Regeln (laws of humans) sollten flexibel über Tabellen/Datenbanken implementiert werden, statische (laws of nature) dürfen hart codiert werden. Das ATC-Beispiel illustriert als Warnung, welcher Aufwand entsteht, wenn man dynamische, sicherheitskritische Regeln fälschlich als statisch behandelt und hart codiert.

## Discovering Business Rules

Dieser Abschnitt erklärt, wie man Business Rules aufspürt (*discover*), da man sie selten durch direktes Nachfragen erhält.

### Grundproblem

1. So wie die Frage „What are your requirements?" bei der Elicitation von User Requirements wenig hilft, bringt auch die Frage an Nutzer „What are your business rules?" wenig.
2. Manchmal erfindet man Business Rules im Verlauf der Arbeit (*invent as you go along*), manchmal tauchen sie während Requirements-Diskussionen auf, und manchmal muss man aktiv nach ihnen suchen.
3. Das Buch verweist auf **Barbara von Halle (2002)**, die einen umfassenden Prozess zum Entdecken von Business Rules beschreibt.

### Übliche Quellen und Methoden zum Auffinden von Rules (nach Boyer and Mili 2011)

1. **„Common knowledge" aus der Organisation** — oft von Personen gesammelt, die lange im Unternehmen gearbeitet haben und die Details der Abläufe kennen.
2. **Legacy systems**, die Business Rules in ihren Requirements und ihrem Code eingebettet haben. Dies erfordert *reverse-engineering* der Rationale hinter Requirements oder Code, um die relevanten Rules zu verstehen. Liefert manchmal nur unvollständiges Wissen über die Business Rules.
3. **Business process modeling** — führt den Analysten dazu, für jeden Prozessschritt nach Rules zu suchen: constraints, triggering events, computational rules und relevant facts.
4. **Analysis of existing documentation** — einschließlich Requirements-Spezifikationen aus früheren Projekten, regulations, industry standards, corporate policy documents, contracts und business plans.
5. **Analysis of data** — z. B. die verschiedenen states, die ein Datenobjekt haben kann, und die Bedingungen, unter denen ein Nutzer oder ein System-Event den state des Objekts ändern kann. Diese Autorisierungen können auch als *roles and permissions matrix* dargestellt werden (wie in Figure 9-2 gezeigt), um Informationen über Rules bezüglich User-Privilege-Levels und Security zu liefern.
6. **Compliance departments** in Unternehmen, die regulierungspflichtige Systeme bauen.

### Warnungen zur Gültigkeit gefundener Rules

1. Nur weil man Business Rules in diesen Quellen gefunden hat, heißt das nicht, dass sie zwingend auf das aktuelle Projekt zutreffen oder überhaupt noch gültig sind.
2. **Beispiel aus dem Buch:** In Legacy-Anwendungen im Code implementierte *computational formulas* könnten obsolet sein.
3. Man muss prüfen (*confirm*), ob aus älteren Dokumenten und Anwendungen gewonnene Rules aktualisiert werden müssen.
4. Man muss den **scope of applicability** der entdeckten Rules bewerten: Sind sie lokal zum Projekt, oder erstrecken sie sich über eine business domain oder das gesamte Unternehmen (enterprise)?

### Einbeziehung von Stakeholdern

1. Oft kennen Projekt-Stakeholder bereits Business Rules, die die Anwendung beeinflussen werden.
2. Bestimmte Mitarbeiter befassen sich teilweise mit speziellen Typen oder Klassen von Rules. Wenn das im eigenen Umfeld der Fall ist, sollte man herausfinden, wer diese Personen sind, und sie in die Diskussion einbeziehen.
3. Der **BA** (Business Analyst) kann Business Rules während Elicitation-Aktivitäten gewinnen, die zugleich andere Requirements-Artefakte und Modelle definieren.
4. In Interviews und Workshops kann der BA Fragen stellen, die die Rationale hinter den von Nutzern präsentierten Requirements und Constraints ausloten (*probe around the rationale*). Diese Diskussionen bringen häufig Business Rules als zugrunde liegende Rationale zum Vorschein.

### Figure 9-3 — Discovering business rules by asking questions from different perspectives

Das Diagramm zeigt im Zentrum eine Wolke **„Business Rules"**, um die herum acht potenzielle Ursprünge von Rules angeordnet sind. Jeder Ursprung ist mit einer Frage verbunden, die ein BA stellen kann, um Rules aus dieser Perspektive aufzudecken:

1. **Policies** → „Why do we have to do it like that?"
2. **Regulations** → „What does the government require?"
3. **Computations** → „How is that number calculated?"
4. **Data Models** → „How are these pieces of data related?"
5. **User Decisions** → „What is a user allowed to do next?"
6. **Events** → „What must happen? What cannot happen?"
7. **System Decisions** → „How does the system know what to do next?"
8. **Object Life Cycles** → „What causes a change in the object's state?"

### Fazit des Abschnitts

Business Rules lassen sich nicht durch direktes Nachfragen ermitteln, sondern müssen aktiv aus verschiedenen Quellen aufgespürt werden: common knowledge, legacy systems, business process modeling, existing documentation, data analysis und compliance departments. Gefundene Rules dürfen nicht ungeprüft übernommen werden — ihre Aktualität und ihr scope of applicability (lokal, domain-weit oder enterprise-weit) müssen bewertet werden. Der BA spielt eine zentrale Rolle, indem er in Elicitation-Aktivitäten gezielt nach der Rationale hinter Requirements fragt. Figure 9-3 liefert dafür ein praktisches Werkzeug: acht Perspektiven (Policies, Regulations, Computations, Data Models, User Decisions, Events, System Decisions, Object Life Cycles) mit jeweils einer passenden Leitfrage.

## Business Rules and Requirements

Dieser Abschnitt erklärt das Verhältnis zwischen Business Rules und functional requirements: Rules treiben die Systemfunktionalität, sind aber nicht dasselbe wie Requirements.

### Abgrenzung Business Rules vs. Functional Requirements

1. Nach dem Identifizieren und Dokumentieren von Business Rules muss man bestimmen, welche davon in der Software implementiert werden müssen.
2. Business Rules und ihre zugehörigen functional requirements sehen sich manchmal sehr ähnlich.
3. Der Unterschied: Rules sind **external statements of policy**, die in der Software durchgesetzt (*enforced*) werden müssen und dadurch die Systemfunktionalität treiben.
4. Jeder BA muss entscheiden: welche Rules seine Anwendung betreffen, welche in der Software enforced werden müssen und *wie* sie enforced werden.

### Beispiel: gleiche Rule, unterschiedliche Functionality

**Beispiel aus dem Buch (Chemical Tracking System):** Es gilt die constraint rule, dass Training-Records aktuell sein müssen, bevor ein Nutzer eine gefährliche Chemikalie anfordern darf.

1. Der Analyst leitet daraus **unterschiedliche** functional requirements ab, je nachdem, ob die Training-Records-Datenbank für das Chemical Tracking System zugänglich ist.
2. **Fall A — Records online verfügbar:** Das System kann den Training-Record des Nutzers nachschlagen und entscheiden, ob es die Anfrage akzeptiert oder ablehnt.
3. **Fall B — Records nicht online verfügbar:** Das System speichert die Chemikalienanfrage temporär und sendet eine Nachricht an den Training Coordinator, der die Anfrage genehmigen oder ablehnen kann.
4. **Kernaussage:** Die Rule ist in beiden Situationen dieselbe, aber die Software-Functionality — die Aktionen, die bei Auftreten der Business Rule während der Ausführung zu ergreifen sind — variiert je nach Umgebung des Systems.

### Beispiel: Rules als Ursprung von System-Features

**Beispiel aus dem Buch:** Zwei Rules zum Ablauf von Chemikalienbehältern:

1. **Rule #1 (action enabler):** „If the expiration date for a chemical container has been reached, then notify the person who currently possesses that container."
2. **Rule #2 (fact):** „A container of a chemical that can form explosive decomposition products expires one year after its manufacture date."

Erläuterung der Verknüpfung:

1. Rule #1 dient als Ursprung für ein System-Feature namens **„Notify chemical owner of expiration"**.
2. Zusätzliche Rules wie #2 helfen dem System zu bestimmen, welche Behälter überhaupt expiration dates haben und deren Owner daher zum richtigen Zeitpunkt benachrichtigt werden müssen.
3. **Beispiel aus dem Buch:** Eine geöffnete Dose Ether (ether) wird unsicher, weil sie in Gegenwart von Sauerstoff explosive Nebenprodukte bilden kann.
4. Aus solchen Rules wird klar, dass das Chemical Tracking System den Status von Behältern mit expiration dates überwachen und die richtigen Personen informieren muss, damit die Behälter zur sicheren Entsorgung zurückgegeben werden.

### Abgeleitete Functional Requirements

Der BA leitet aus dem Feature folgende vier functional requirements ab (Beispiele aus dem Buch):

1. **Expired.Notify.Before** — Wenn der Status eines Behälters mit expiration date nicht *Disposed* ist, soll das System den aktuellen Owner eine Woche **vor** dem Ablaufdatum benachrichtigen.
2. **Expired.Notify.Date** — … das System soll den Owner **am** Ablaufdatum benachrichtigen.
3. **Expired.Notify.After** — … das System soll den Owner eine Woche **nach** dem Ablaufdatum benachrichtigen.
4. **Expired.Notify.Manager** — … das System soll den **Manager** des aktuellen Owners zwei Wochen **nach** dem Ablaufdatum benachrichtigen.

Gemeinsame Bedingung aller vier: Sie greifen nur, wenn der Behälter ein expiration date hat und sein Status nicht *Disposed* ist.

### Empfehlung: Tabellendarstellung statt Liste (Wiegers 2006)

1. Wenn man auf eine Menge sehr ähnlicher Requirements wie diese trifft, sollte man erwägen, sie als **Tabelle** statt als Liste darzustellen.
2. Vorteile: kompakter und leichter zu reviewen, zu verstehen und zu modifizieren.
3. Zudem ermöglicht es eine prägnantere Beschriftung (*label*) der Requirements, weil die Tabelle nur die **suffixes** zeigen muss, die an das Label des parent requirement angehängt werden.
4. Das parent requirement lautet dann **Expired.Notify**: Wenn der Status eines Behälters mit expiration date nicht *Disposed* ist, soll das System die in der folgenden Tabelle gezeigten Personen zu den angegebenen Zeitpunkten benachrichtigen.

#### Tabellen-Darstellung der vier Requirements

| Requirement ID | Who to notify | When to notify |
|---|---|---|
| .Before | Container's current owner | One week before expiration date |
| .Date | Container's current owner | On expiration date |
| .After | Container's current owner | One week after expiration date |
| .Manager | Manager of container's current owner | Two weeks after expiration date |

Die Spalte **Requirement ID** zeigt nur die Suffixe (.Before, .Date, .After, .Manager), die an das parent label **Expired.Notify** angehängt werden. Die Spalten **Who to notify** und **When to notify** ersetzen den repetitiven Text der vier ursprünglichen Listen-Requirements.

### Fazit des Abschnitts

Business Rules und functional requirements sehen oft ähnlich aus, sind aber zu unterscheiden: Rules sind external statements of policy, während Requirements die konkrete Software-Functionality festlegen, mit der eine Rule enforced wird. Dieselbe Rule kann je nach Systemumgebung zu unterschiedlichen functional requirements führen (Chemical-Tracking-Beispiel mit/ohne Online-Zugriff auf Training-Records). Rules (action enabler, fact) dienen als Ursprung von Features und den daraus abgeleiteten Requirements (Notify-Beispiel). Bei mehreren sehr ähnlichen Requirements empfiehlt das Buch (Wiegers 2006) eine **Tabellendarstellung** statt einer Liste, da sie kompakter, leichter review- und modifizierbar ist und die Beschriftung über Suffixe an ein parent requirement vereinfacht.

## Tying Everything Together

Dieser Abschnitt erklärt, wie man functional requirements mit ihren zugrunde liegenden Business Rules verknüpft, ohne die Rules zu duplizieren.

### Grundprinzip: keine Duplizierung

1. Um Redundanz zu vermeiden, soll man Rules aus dem business rules catalog **nicht** in der Requirements-Dokumentation duplizieren.
2. Stattdessen soll man auf spezifische Rules **zurückverweisen** als Ursprung bestimmter Functionality oder Algorithmen.

### Drei Möglichkeiten, die Links zu definieren

Die Verknüpfung zwischen einem functional requirement und seinen parent business rules kann auf mehrere Arten definiert werden; das Buch nennt drei Möglichkeiten:

1. **Über ein requirements management tool:** Man erstellt ein requirement attribute namens **„Origin"** und gibt die Rules als Origin der abgeleiteten (*derived*) functional requirements an. (Verweis im Buch auf Chapter 27, „Requirements management practices.")
2. **Über Traceability-Links:** Man definiert traceability links zwischen functional requirements und den verbundenen Business Rules in einer *requirements traceability matrix* oder einer *requirements mapping matrix* (Beatty and Chen 2012). Das ist am einfachsten, wenn die Business Rules im selben Repository wie die Requirements gespeichert sind. (Verweis auf Chapter 29, „Links in the requirements chain.")
3. **Über Hyperlinks:** Wenn Business Rules und Requirements in Textverarbeitungs- oder Spreadsheet-Dateien gespeichert sind, definiert man Hyperlinks von den Business-Rule-ID-Referenzen in den Requirements zurück zu den anderswo gespeicherten Beschreibungen der Business Rules. **Warnung aus dem Buch:** Hyperlinks brechen leicht, wenn sich der Speicherort der Rules-Sammlung ändert.

### Vorteile und Trade-off des Verlinkens

1. Diese Links halten die Requirements aktuell bei Rule-Änderungen, weil die Requirements einfach auf die **master instance** der Rule zeigen.
2. Ändert sich eine Rule, kann man nach der verlinkten Rule-ID suchen, um Requirements — oder implementierte Functionality — zu finden, die man eventuell ändern muss.
3. Solche Links erleichtern die **Wiederverwendung** derselben Rule an mehreren Stellen und in mehreren Projekten, weil die Rules nicht in der Dokumentation einer einzelnen Anwendung vergraben sind.
4. **Trade-off:** Ein Entwickler, der die SRS liest, muss dem Cross-Reference-Link folgen, um an die Rule-Details zu gelangen. Das ist der Trade-off, der entsteht, wenn man sich entscheidet, Information *nicht* zu duplizieren (Wiegers 2006).

### Abschließende Einordnung

1. Wie bei so vielen Aspekten des Requirements Engineering gibt es keine einfache, perfekte Lösung für das Management von Business Rules, die in allen Situationen funktioniert.
2. Sobald man aber aktiv beginnt, Business Rules zu suchen, festzuhalten und anzuwenden, wird die Rationale hinter den Entscheidungen der Anwendungsentwicklung für alle Stakeholder klarer.

### Next Steps (Empfohlene nächste Schritte aus dem Buch)

1. Versuche, mindestens einen Vertreter jedes Business-Rule-Typs aus der Taxonomie in Figure 9-1 für dein aktuelles Projekt zu identifizieren.
2. Beginne, einen **business rules catalog** mit den Rules zu befüllen, die dein aktuelles Projekt betreffen. Klassifiziere die Rules nach dem Schema in Figure 9-1 und notiere den Origin jeder Rule.
3. Richte eine **traceability matrix** ein, die angibt, welche functional requirements jede identifizierte Business Rule enforced.
4. Identifiziere die Rationale hinter jedem deiner functional requirements, um weitere, implizite Business Rules zu entdecken.

### Fazit des Abschnitts

Functional requirements sollen nicht die Business Rules duplizieren, sondern über Links auf deren master instance verweisen. Das Buch nennt drei Verknüpfungsmethoden: ein **„Origin"-Attribut** in einem Requirements-Management-Tool, **traceability links** in einer traceability/mapping matrix oder **Hyperlinks** in Text-/Spreadsheet-Dateien (mit der Warnung, dass Hyperlinks leicht brechen). Der Vorteil von Links ist Aktualität bei Änderungen und Wiederverwendbarkeit; der Trade-off ist, dass Leser dem Link folgen müssen, um Details zu sehen. Es gibt keine perfekte Universallösung, aber aktives Suchen, Festhalten und Anwenden von Business Rules macht die Rationale der Entwicklungsentscheidungen für alle Stakeholder transparenter. Die **Next Steps** geben vier konkrete Handlungsempfehlungen für das eigene Projekt.