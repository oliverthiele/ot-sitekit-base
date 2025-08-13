# Datenbank

## Zusätzliche Felder für Content-Elemente

### ot_layout

Typ: varchar

Dieses Feld wird initial mit einem TCA für ein Drop-Down versehen, ohne die
Drop-Down-Elemente für verschiedene Layouts zu definieren. Dies ist dann
die Aufgabe der einzelnen Inhaltselemente.

Das neue Datenbankfeld ist ein Ersatz für das TYPO3-Datenbank-Feld "layout".

Unterschiede zu dem TYPO3-Core-Feld "layout":

- Es können Zeichenketten verwendet werden.
- Es gibt nicht bereits drei Standard-Elemente mit Layout 1, Layout 2 und Layout 3

In diesen Extensions von mir wird dieses Feld bei der Installation hinzugefügt bzw.
das TCA modifiziert:

- ot_heroimage

  Umschaltung von Abstand/ kein Abstand

- ot_sitekit_base

  Umschaltung der maximalen Anzahl von Spalten (2 – 4)


### header_style

Zur Trennung zwischen Semantik und Layout kann jetzt z. B. eine
Überschrift 1. Ordnung wie eine Überschrift 4. Ordnung aussehen.
Auch die Bootstrap 5 Display-Klassen funktionieren.
Für unsichtbare Überschriften, die im Screenreader vorgelesen werden, gibt es
auch eine Option.
