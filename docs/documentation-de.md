# Beitragsanalyse – Plugin für Admidio

**Version:** 1.0.0  
**Datum:** April 2026  
**Autor:** Pascal Christmann  
**Lizenz:** GNU General Public License v2.0  
**Mindest-Admidio-Version:** 5.1.0  

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Admidio-Datenbasis einrichten](#3-admidio-datenbasis-einrichten)
   - 3.1 [Kategorie Sportgruppen anlegen](#31-kategorie-sportgruppen-anlegen)
   - 3.2 [Rollen für Sportgruppen anlegen](#32-rollen-für-sportgruppen-anlegen)
   - 3.3 [Kategorie Familienmitgliedschaften anlegen](#33-kategorie-familienmitgliedschaften-anlegen)
   - 3.4 [Profilfeld Beitrag prüfen](#34-profilfeld-beitrag-prüfen)
4. [Installation](#4-installation)
   - 4.1 [Plugin-Dateien kopieren](#41-plugin-dateien-kopieren)
   - 4.2 [Plugin im Plugin-Manager aktivieren](#42-plugin-im-plugin-manager-aktivieren)
   - 4.3 [Menüeintrag erstellen](#43-menüeintrag-erstellen)
   - 4.4 [Zugriff auf den Menüeintrag einschränken](#44-zugriff-auf-den-menüeintrag-einschränken)
5. [Einstellungen](#5-einstellungen)
   - 5.1 [Plugin aktiviert](#51-plugin-aktiviert)
   - 5.2 [Sichtbar für Rollen](#52-sichtbar-für-rollen)
   - 5.3 [Kategorie Sportgruppen](#53-kategorie-sportgruppen)
   - 5.4 [Kategorie Familienmitgliedschaften](#54-kategorie-familienmitgliedschaften)
   - 5.5 [Profilfeld Beitrag](#55-profilfeld-beitrag)
6. [Bedienung](#6-bedienung)
   - 6.1 [Übersichtstabelle – Beiträge je Sparte](#61-übersichtstabelle--beiträge-je-sparte)
   - 6.2 [Detailtabelle – Anteil je Mitglied](#62-detailtabelle--anteil-je-mitglied)
7. [Berechnungslogik](#7-berechnungslogik)
   - 7.1 [Einzelmitglieder](#71-einzelmitglieder)
   - 7.2 [Familienmitgliedschaften](#72-familienmitgliedschaften)
   - 7.3 [Mitglieder ohne Sportgruppenzuordnung](#73-mitglieder-ohne-sportgruppenzuordnung)
   - 7.4 [Rechenbeispiel](#74-rechenbeispiel)
8. [Zugriffsrechte im Detail](#8-zugriffsrechte-im-detail)
9. [Tipps und Fehlerbehebung](#9-tipps-und-fehlerbehebung)
10. [Changelog](#10-changelog)

---

## 1. Übersicht

Das Plugin **Beitragsanalyse** ermittelt, welcher Anteil der gesamten Mitgliedsbeiträge
einer Organisation auf die einzelnen Sportgruppen (Sparten) entfällt. Es liest die Daten
direkt aus der Admidio-Datenbank, sodass kein vorheriger CSV-Export erforderlich ist.

**Hauptfunktionen:**

- Anteilige Verteilung des individuellen Mitgliedsbeitrags auf alle Sparten, denen
  das Mitglied angehört.
- Sonderbehandlung von Familienmitgliedschaften: Der Familienbeitrag wird zunächst
  gleichmäßig auf alle Familienmitglieder aufgeteilt, anschließend je Mitglied anteilig
  auf dessen Sparten.
- Übersichtstabelle mit dem Gesamtbetrag je Sparte und einer Summenzeile.
- Detailtabelle mit dem genauen Anteil je Mitglied und Sparte, sortiert nach Nachname.
- Rollenbasierte Zugriffskontrolle: Die Auswertung ist nur für autorisierte Mitglieder
  sichtbar.
- Konfigurierbar über die Plugin-Einstellungen in Admidio ohne Codeänderungen.

---

## 2. Voraussetzungen

| Anforderung | Mindestversion |
|---|---|
| Admidio | 5.1.0 |
| PHP | 8.2 |
| Datenbank | MySQL 5.7 / MariaDB 10.3 / PostgreSQL 11 |

Das Plugin nutzt ausschließlich Standardfunktionen von Admidio 5.1 und hat keine
externen Abhängigkeiten.

---

## 3. Admidio-Datenbasis einrichten

Bevor das Plugin sinnvoll genutzt werden kann, müssen in Admidio die entsprechenden
Kategorien, Rollen und ein Profilfeld vorhanden sein. Falls diese bereits existieren,
können die Abschnitte 3.1–3.4 übersprungen werden.

### 3.1 Kategorie Sportgruppen anlegen

1. In Admidio: **Administration → Rollen → Rollenkategorien verwalten**
2. Neue Kategorie anlegen, z. B. mit dem Namen **„Sportgruppen"**.
3. Typ: **Rollen** (Standard).
4. Speichern.

> **Hinweis:** Der Name der Kategorie ist frei wählbar. Im Plugin wird später diese
> Kategorie ausgewählt – alle darin enthaltenen Rollen gelten dann als Sparten.

### 3.2 Rollen für Sportgruppen anlegen

1. **Administration → Rollen → Neue Rolle erstellen**
2. Für jede Sportgruppe eine eigene Rolle anlegen, z. B.:
   - Bogenschießen
   - Capoeira
   - Fußball
   - Judo
   - Nordic Walking
   - Rückenschule
   - Strong Nation
   - Yoga
   - Zumba
3. Als Kategorie jeweils **„Sportgruppen"** (aus Schritt 3.1) auswählen.
4. Mitglieder der jeweiligen Sportgruppe der Rolle zuordnen
   (**Administration → Mitglieder → Rollenmitgliedschaft bearbeiten**).

### 3.3 Kategorie Familienmitgliedschaften anlegen

Dieser Schritt ist **optional**. Er ist nur nötig, wenn im Verein Familienbeiträge
existieren, bei denen mehrere Mitglieder einen gemeinsamen Beitrag teilen.

1. **Administration → Rollen → Rollenkategorien verwalten**
2. Neue Kategorie anlegen, z. B. **„Familien"**.
3. Für jede Familie eine eigene Rolle anlegen, z. B. **„Familie Lindner"**.
4. Alle Mitglieder der Familie dieser Rolle zuordnen.
5. Den Familienbeitrag als Profilfeld-Wert bei **genau einem** Familienmitglied
   eintragen (typischerweise beim Hauptmitglied). Die anderen Familienmitglieder
   erhalten den Wert 0 oder lassen das Feld leer.

> **Wichtig:** Das Plugin sucht automatisch nach dem ersten Familienmitglied mit
> einem Beitragswert > 0 und verwendet diesen als gemeinsamen Familienbeitrag.

### 3.4 Profilfeld Beitrag prüfen

1. **Administration → Profileinstellungen**
2. Prüfen, ob ein Profilfeld für den Mitgliedsbeitrag existiert (z. B. **„Beitrag"**
   oder **„Jahresbeitrag"**).
3. Falls nicht vorhanden: Neues Feld anlegen mit dem Feldtyp **„Dezimalzahl"** oder
   **„Ganze Zahl"**.
4. Den Beitrag in Euro (Komma als Dezimaltrennzeichen ist erlaubt) bei jedem Mitglied
   im Profil eintragen.

> **Tipp:** Das Plugin akzeptiert Beiträge mit Komma als Dezimaltrennzeichen
> (z. B. „84,00") und wandelt diese intern in einen Zahlenwert um.

---

## 4. Installation

### 4.1 Plugin-Dateien kopieren

1. Laden Sie das Plugin-Paket herunter oder erstellen Sie den Ordner `beitragsanalyse`
   aus den bereitgestellten Quelldateien.
2. Kopieren Sie den **vollständigen Ordner** `beitragsanalyse` in das Verzeichnis
   `plugins/` Ihrer Admidio-Installation:

   ```
   <admidio-root>/
   └── plugins/
       └── beitragsanalyse/        ← hier einfügen
           ├── beitragsanalyse.json
           ├── index.php
           ├── classes/
           ├── templates/
           └── languages/
   ```

3. Stellen Sie sicher, dass der Webserver-Benutzer Leserechte auf alle Dateien hat.

> **Achtung:** Kopieren Sie den Ordner `beitragsanalyse` selbst in `plugins/`,
> nicht nur dessen Inhalt. Der Ordnername muss exakt `beitragsanalyse` lauten.

### 4.2 Plugin im Plugin-Manager aktivieren

1. Melden Sie sich als Administrator bei Admidio an.
2. Gehen Sie zu **Administration → Plugins**.
3. Das Plugin **„Beitragsanalyse"** erscheint in der Liste der verfügbaren Plugins.
4. Klicken Sie auf **„Installieren"** bzw. **„Aktivieren"**.
5. Der Plugin-Manager richtet die notwendigen Datenbankeinträge automatisch ein.

### 4.3 Menüeintrag erstellen

Damit das Plugin über das Admidio-Menü erreichbar ist, muss ein Menüeintrag angelegt
werden:

1. **Administration → Menü**
2. Klicken Sie auf **„Neuen Menüeintrag erstellen"**.
3. Füllen Sie die Felder aus:

   | Feld | Wert |
   |---|---|
   | Name | z. B. „Beitragsanalyse" |
   | URL | `{ADMIDIO_URL}/plugins/beitragsanalyse/index.php` |
   | Icon | z. B. `bi-bar-chart` oder `fa-chart-bar` |
   | Übergeordnetes Menü | Hauptmenü oder ein Untermenü Ihrer Wahl |

4. Speichern.

### 4.4 Zugriff auf den Menüeintrag einschränken

Der Menüeintrag selbst kann bereits auf bestimmte Rollen eingeschränkt werden – das
ist die **erste Sicherheitsschicht**. Mitglieder ohne die erforderliche Rolle sehen
den Menüpunkt gar nicht erst.

1. In der Menüverwaltung den neu erstellten Eintrag bearbeiten.
2. Unter **„Sichtbar für Rollen"** die gewünschten Rollen auswählen, z. B.
   „Vorstand" oder „Kassenwart".
3. Speichern.

> **Hinweis:** Zusätzlich bietet das Plugin eine **zweite Sicherheitsschicht** in den
> Plugin-Einstellungen (Abschnitt 5.2). Beide Mechanismen können kombiniert werden.

---

## 5. Einstellungen

Die Plugin-Einstellungen werden über **Administration → Plugins → Beitragsanalyse →
Einstellungen** aufgerufen.

### 5.1 Plugin aktiviert

| | |
|---|---|
| **Werte** | Aktiviert / Deaktiviert |
| **Standard** | Aktiviert |

Deaktiviert das Plugin vollständig. Bei deaktiviertem Plugin sehen alle Benutzer
nur die Meldung *„Modul deaktiviert"*, auch wenn sie die nötigen Zugriffsrechte hätten.

---

### 5.2 Sichtbar für Rollen

| | |
|---|---|
| **Werte** | Mehrfachauswahl aus allen verfügbaren Rollen |
| **Standard** | (leer = alle angemeldeten Mitglieder) |

Legt fest, welche Mitglieder das Plugin öffnen und die Auswertung sehen dürfen.
Nur Mitglieder, die **mindestens einer** der ausgewählten Rollen angehören, erhalten
Zugriff.

Ist keine Rolle ausgewählt, können alle angemeldeten Mitglieder das Plugin aufrufen.
Nicht angemeldete Besucher haben in keinem Fall Zugriff.

**Empfehlung:** Wählen Sie hier z. B. die Rollen „Vorstand" und „Kassenwart" aus,
damit die Beitragsauswertung nur für berechtigte Personen sichtbar ist.

---

### 5.3 Kategorie Sportgruppen

| | |
|---|---|
| **Werte** | Auswahl aus allen Rollenkategorien vom Typ „ROL" |
| **Standard** | (nicht gesetzt) |

**Pflichtfeld.** Wählen Sie die Rollenkategorie aus, deren Rollen als Sportgruppen
(Sparten) für die Beitragsverteilung gelten sollen.

Alle aktiven Rollenmitgliedschaften in dieser Kategorie werden für die Berechnung
herangezogen. Eine Rolle ohne aktive Mitglieder wird ignoriert.

> **Ohne diese Einstellung zeigt das Plugin nur eine Konfigurationswarnung an.**

---

### 5.4 Kategorie Familienmitgliedschaften

| | |
|---|---|
| **Werte** | Auswahl aus allen Rollenkategorien vom Typ „ROL" |
| **Standard** | (nicht gesetzt = deaktiviert) |

**Optionales Feld.** Wählen Sie die Rollenkategorie aus, deren Rollen Familien
repräsentieren.

Ist dieses Feld leer, werden alle Mitglieder als Einzelmitglieder behandelt.
Ist eine Kategorie ausgewählt, werden alle Mitglieder, die einer Rolle in dieser
Kategorie angehören, als Familienmitglieder erkannt und ihr Beitrag wird nach der
Familienlogik berechnet (siehe Abschnitt 7.2).

---

### 5.5 Profilfeld Beitrag

| | |
|---|---|
| **Werte** | Auswahl aus Profilfeldern vom Typ Dezimalzahl, Ganze Zahl oder Text |
| **Standard** | (nicht gesetzt) |

**Pflichtfeld.** Wählen Sie das Profilfeld aus, das den Mitgliedsbeitrag als Zahl
enthält.

> **Hinweis:** Das Feld muss pro Mitglied einen numerischen Wert (in Euro) enthalten.
> Komma als Dezimaltrennzeichen wird akzeptiert. Leere Felder oder Werte ≤ 0 werden
> als „kein Beitrag" gewertet und fließen nicht in die Berechnung ein.

---

## 6. Bedienung

Nachdem die Einstellungen konfiguriert wurden, öffnet ein berechtigtes Mitglied das
Plugin über den zugehörigen Menüeintrag. Die Seite lädt automatisch und zeigt die
aktuellen Daten aus der Datenbank – es ist keine weitere Benutzereingabe erforderlich.

### 6.1 Übersichtstabelle – Beiträge je Sparte

Die erste Tabelle fasst die verteilten Beiträge je Sportgruppe zusammen:

```
┌─────────────────────┬────────────────────┐
│ Sparte              │ Summe Beiträge (€) │
├─────────────────────┼────────────────────┤
│ Bogenschießen       │         245,00     │
│ Capoeira            │         312,00     │
│ Fußball             │         876,00     │
│ Judo                │         540,00     │
│ Nordic Walking      │         198,00     │
│ Rückenschule        │         467,00     │
│ Strong Nation       │         325,00     │
│ Yoga                │         289,00     │
│ Zumba               │         434,00     │
│ Zumba Kids          │         112,00     │
├─────────────────────┼────────────────────┤
│ Keine Sparte        │          48,00     │
├─────────────────────┼────────────────────┤
│ Summe               │       3.846,00     │
└─────────────────────┴────────────────────┘
```

- **Sparte:** Name der Sportgruppe (entspricht dem Rollennamen in Admidio).
- **Summe Beiträge:** Summe aller anteiligen Beitragszuordnungen für diese Sparte.
- **Keine Sparte:** Beiträge von Mitgliedern, die zwar einen Beitrag haben, aber
  keiner Sportgruppe zugeordnet sind.
- **Summe:** Gesamtsumme aller verteilten Beiträge.

Die Tabelle ist sortierbar (DataTables). Ein Klick auf den Spaltenkopf sortiert
die Zeilen alphabetisch oder numerisch.

### 6.2 Detailtabelle – Anteil je Mitglied

Die zweite Tabelle zeigt den genauen Beitragsanteil je Mitglied und Sparte:

```
┌────────────┬────────────┬───────────────┬───────────┬─────────────────┐
│ Nachname   │ Vorname    │ Sparte        │ Anteil(€) │ Familie         │
├────────────┼────────────┼───────────────┼───────────┼─────────────────┤
│ Baum       │ Jonas      │ Fußball       │     96,00 │                 │
│ Keller     │ Sara       │ Yoga          │     42,00 │                 │
│ Keller     │ Sara       │ Zumba         │     42,00 │                 │
│ Lindner    │ Elena      │ Judo          │     52,00 │ Familie Lindner │
│ Lindner    │ Finn       │ Judo          │     52,00 │ Familie Lindner │
│ Lindner    │ Nina       │ Judo          │     52,00 │ Familie Lindner │
│ …          │ …          │ …             │         … │                 │
└────────────┴────────────┴───────────────┴───────────┴─────────────────┘
```

- **Nachname / Vorname:** Name des Mitglieds (nach Nachname sortiert).
- **Sparte:** Sportgruppe, auf die der Anteil entfällt.
- **Anteil:** Berechneter Beitragsanteil für genau diese Sparte, in Euro.
- **Familie:** Name der Familienrolle, falls das Mitglied über eine
  Familienmitgliedschaft abgerechnet wird. Bei Einzelmitgliedern leer.

> **Hinweis:** Ein Mitglied erscheint in der Detailtabelle mehrfach, wenn es mehreren
> Sportgruppen angehört – für jede Sparte eine eigene Zeile.

---

## 7. Berechnungslogik

Das Plugin verteilt die Mitgliedsbeiträge nach folgenden Regeln. Die Logik ist
identisch mit der des eigenständigen Python-Skripts `stats.py`.

### 7.1 Einzelmitglieder

Ein Mitglied, das **keiner Familienkategorie** angehört, wird als Einzelmitglied
behandelt:

```
Anteil je Sparte = Beitrag des Mitglieds ÷ Anzahl seiner aktiven Sparten
```

**Beispiel:**  
Sara Keller zahlt 84,00 € und ist in den Sparten Yoga und Zumba aktiv.

```
Anteil Yoga  = 84,00 ÷ 2 = 42,00 €
Anteil Zumba = 84,00 ÷ 2 = 42,00 €
```

### 7.2 Familienmitgliedschaften

Alle Mitglieder, die derselben Familienrolle angehören, teilen einen gemeinsamen
Beitrag:

1. **Familienbeitrag ermitteln:** Das Plugin sucht das erste Familienmitglied
   mit einem Beitragswert > 0. Dieser Wert gilt als Gesamtbeitrag der Familie.
   *(In der Praxis trägt genau ein Familienmitglied den Beitrag, alle anderen
   haben den Wert 0 oder kein Eintrag.)*

2. **Anteil je Familienmitglied:**
   ```
   Anteil je Mitglied = Familienbeitrag ÷ Anzahl Familienmitglieder
   ```

3. **Anteil je Sparte:**
   ```
   Anteil je Sparte = Anteil je Mitglied ÷ Anzahl Sparten des Mitglieds
   ```

**Beispiel:**  
Familie Lindner (3 Mitglieder: Elena, Nina, Finn) zahlt zusammen 156,00 €.
Alle drei sind nur in der Sparte Judo aktiv.

```
Anteil je Mitglied  = 156,00 ÷ 3 =  52,00 €
Anteil Judo (Elena) =  52,00 ÷ 1 =  52,00 €
Anteil Judo (Nina)  =  52,00 ÷ 1 =  52,00 €
Anteil Judo (Finn)  =  52,00 ÷ 1 =  52,00 €
Summe Judo (Familie Lindner) = 156,00 €
```

**Erweitertes Beispiel mit mehreren Sparten:**  
Familie Fuchs (2 Mitglieder: Lea, Ben) zahlt 108,00 €.
Lea ist in Yoga und Zumba aktiv, Ben nur in Fußball.

```
Anteil je Mitglied = 108,00 ÷ 2 = 54,00 €

Lea:
  Anteil Yoga  = 54,00 ÷ 2 = 27,00 €
  Anteil Zumba = 54,00 ÷ 2 = 27,00 €

Ben:
  Anteil Fußball = 54,00 ÷ 1 = 54,00 €
```

### 7.3 Mitglieder ohne Sportgruppenzuordnung

Hat ein Mitglied einen Beitrag, ist aber **keiner Sportgruppe** zugeordnet, wird
der Beitrag (bzw. bei Familien der anteilige Beitrag) in der Zeile **„Keine Sparte"**
der Übersichtstabelle erfasst. Diese Zeile erscheint nur, wenn mindestens ein
solcher Betrag vorhanden ist.

### 7.4 Rechenbeispiel – Gesamtübersicht

| Mitglied | Typ | Beitrag | Sparten | Anteil Fußball | Anteil Yoga | Anteil Zumba |
|---|---|---|---|---|---|---|
| Baum, Jonas | Einzel | 96,00 € | Fußball | 96,00 € | – | – |
| Keller, Sara | Einzel | 84,00 € | Yoga, Zumba | – | 42,00 € | 42,00 € |
| Lindner, Elena | Familie (Lindner) | *156,00 € gesamt* | Judo | – | – | – |
| Lindner, Nina | Familie (Lindner) | | Judo | – | – | – |
| Lindner, Finn | Familie (Lindner) | | Judo | – | – | – |

*Die Spalte „Judo" ergibt sich zu 156,00 €.*

---

## 8. Zugriffsrechte im Detail

Das Plugin verwendet zwei unabhängige Sicherheitsschichten:

### Schicht 1 – Menüeintrag (Admidio-Standard)

Der Menüeintrag selbst ist nur für ausgewählte Rollen sichtbar. Mitglieder ohne
die erforderliche Rolle sehen den Menüpunkt gar nicht und können die Plugin-URL
auch bei direktem Aufruf nicht aufrufen, da Admidio den Zugriff auf den Menüpunkt
prüft.

**Konfiguration:** Administration → Menü → Eintrag bearbeiten → „Sichtbar für Rollen"

### Schicht 2 – Plugin-Einstellung (Abschnitt 5.2)

Als zusätzliche Absicherung prüft das Plugin beim Aufruf selbst, ob das aktuell
angemeldete Mitglied einer der in den Einstellungen hinterlegten Rollen angehört.
Bei fehlendem Recht erscheint lediglich die Meldung „Keine Berechtigung" – ohne
weitere Details.

**Konfiguration:** Administration → Plugins → Beitragsanalyse → Einstellungen →
„Sichtbar für Rollen"

### Empfohlene Konfiguration

Beide Schichten sollten übereinstimmend auf die Rollen **Vorstand** und **Kassenwart**
(oder die bei Ihnen entsprechenden Rollen) eingeschränkt werden:

```
Menüeintrag → Sichtbar für Rollen:   Vorstand, Kassenwart
Plugin-Einstellung → Sichtbar:        Vorstand, Kassenwart
```

---

## 9. Tipps und Fehlerbehebung

**Problem:** Das Plugin zeigt die Meldung „Das Plugin ist noch nicht vollständig
konfiguriert."

→ Öffnen Sie die Plugin-Einstellungen und wählen Sie mindestens die
**Kategorie Sportgruppen** und das **Profilfeld Beitrag** aus. Beide Felder sind
Pflichtfelder.

---

**Problem:** Die Tabellen sind leer, obwohl Beiträge und Rollenmitgliedschaften
vorhanden sind.

→ Prüfen Sie, ob die Mitgliedschaft in den Sparten-Rollen **aktiv** ist, d. h.
das Enddatum der Rollenmitgliedschaft liegt in der Zukunft. Abgelaufene
Mitgliedschaften werden nicht berücksichtigt.

→ Prüfen Sie, ob der Beitrag im Profil des Mitglieds als positive Zahl eingetragen
ist. Leere Felder und der Wert 0 werden nicht berücksichtigt.

---

**Problem:** Die Summe „Keine Sparte" ist unerwartet hoch.

→ Einige Mitglieder haben einen Beitrag, sind aber keiner Rolle in der
Sportgruppen-Kategorie zugeordnet. Öffnen Sie die Detailtabelle und filtern
Sie nach leerem Sparten-Feld, um diese Mitglieder zu identifizieren.

---

**Problem:** Familienmitglieder werden als Einzelmitglieder behandelt.

→ Prüfen Sie, ob die Familienmitgliedschafts-Kategorie in den Einstellungen
ausgewählt ist.

→ Prüfen Sie, ob alle Familienmitglieder derselben Familienrolle zugeordnet sind.

→ Prüfen Sie, ob **genau ein** Familienmitglied einen Beitragswert > 0 hat. Hat
kein Mitglied einen Wert, wird die Familie vollständig ignoriert.

---

**Problem:** Das Plugin erscheint nicht in der Plugin-Manager-Liste.

→ Stellen Sie sicher, dass der Ordner `beitragsanalyse` direkt im Verzeichnis
`plugins/` liegt und die Datei `beitragsanalyse.json` enthält.

→ Prüfen Sie die Dateiberechtigungen: Der Webserver muss die Dateien lesen können.

---

**Problem:** „Modul deaktiviert"-Meldung trotz korrekter Einstellungen.

→ Öffnen Sie die Plugin-Einstellungen und setzen Sie **Plugin aktiviert** auf
**„Aktiviert"**.

---

## 10. Changelog

### Version 1.0.0 – April 2026

- Erstveröffentlichung
- Anteilige Beitragsverteilung auf Sparten (Einzelmitglieder)
- Sonderbehandlung von Familienmitgliedschaften
- Übersichtstabelle je Sparte
- Detailtabelle je Mitglied mit Familienanzeige
- Rollenbasierte Zugriffskontrolle (zwei Schichten)
- Plugin-Einstellungen: Kategorie Sportgruppen, Kategorie Familien,
  Profilfeld Beitrag, Sichtbar für Rollen
- Zweisprachige Oberfläche (Deutsch, Englisch)
- Kompatibel mit Admidio 5.1.0+

---

*Dieses Plugin steht unter der GNU General Public License v2.0.*  
*Admidio ist ein eingetragenes Warenzeichen des Admidio-Teams.*
