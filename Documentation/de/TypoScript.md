

Derzeit wird noch FluidStylesContent genutzt, um weniger Probleme wegen Abhängigkeiten zu bekommen.
Langfristig soll die Abhängigkeit komplett entfernt werden.

Daher ist die Konfiguration bei lib.contentElement so aufgebaut:

* 0  -> EXT:fluid_styled_content/Resources/Private/…
* 10 -> {$styles.templates.layoutRootPath} (unbenutzt)
* 15 -> EXT:ot_irrebuttons/Resources/Private/ContentElements/…
* 60 -> EXT:ot_sitekitbase/Resources/Private/ContentElements/…
* 70 -> Optionale Themes
* 80 -> EXT:my_sitekit/Resources/Private/ContentElements/…

Bei allen Pfaden zu Templates, Layouts und Partials muss ein Ordner sein, der
über `{$sitekit.frameworks.frontend.directory}/` gesetzt wird. Dies ermöglicht
entweder, trotz späterer Sitekit Extension Updates,
die Templates auf dem alten Stand (z.B. Bootstrap5) zu lassen, oder ein Austausch aller Templates
in allen Sitekit Extensions mit nur einer einzigen Änderung (zu z. B. Bootstrap6).


Templates für Page

* 60 -> EXT:ot_sitekitbase/Resources/Private/ContentElements/…
* 70 -> Optionale Themes
* 80 -> EXT:my_sitekit/Resources/Private/ContentElements/…


## Welche Zahlen für welche Extensions

### 0 & 10 Fluid Styled Content

Die Standard-Templates von TYPO3

### 11 - 59 Extensions, die lib.contentElement erweitern

Gilt für alle Inhaltselemente, die später `tt_content.<CType> =< lib.contentElement` nutzen.

### 60 Sitekit Base

Hier werden beispielsweise die Templates von Fluid Styled Content überschrieben,
damit sie die Bootstrap 5 CSS Klassen nutzen.

### 70 Sitekit Themes

Themes sind noch nicht integriert, aber diese müssten mit einer kleineren Zahl
als das Sitepackage mit den individuellen Anpassungen konfiguriert sein, damit
die Theme-Templates im Sitepackage noch überschrieben werden können.

### 80 Sitepackage
mit individuellen Anpassungen
