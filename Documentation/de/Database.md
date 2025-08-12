# Database

## New fields

### ot_layout

Das neue Datenbankfeld ist ein Ersatz für das TYPO3-Datenbank-Feld "layout".

Unterschiede zu dem Core-Feld:

- Es können Zeichenketten verwendet werden.
- Es gibt nicht bereits drei Standard-Elemente mit Layout 1, Layout 2 und Layout 3

### header_style

Zur Trennung zwischen Semantik und Layout kann jetzt z. B. eine
Überschrift 1. Ordnung wie eine Überschrift 4. Ordnung aussehen.
Auch die Bootstrap 5 Display-Klassen funktionieren.
Für unsichtbare Überschriften, die im Screenreader vorgelesen werden, gibt es
auch eine Option.
