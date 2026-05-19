(function (il) {
    'use strict';

    /**
     * Handling fixation of settings
     */
    class Fixation {

        constructor() {
            this.nodes = [];
        }

        addNode(id, visible) {
            const node = document.getElementById(id);
            if (node) {
                this.nodes.push(node);
                if (!visible) {
                    node.classList.add('ilNoDisplay');
                }
            }
        }

        toggleNodes() {
           this.nodes.forEach(n => n.classList.toggle('ilNoDisplay'));
        }

        enableTemplate(target_url, value) {
            const target = new URL(window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/') + '/' + target_url);
            target.searchParams.set('enable', value);
            window.location = target;
        }

        updateGroup = function(target_url, group, value) {
            const target = new URL(window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/') + '/' + target_url);
            target.searchParams.set('group', group);
            target.searchParams.set('enable', value);
            window.location = target;
        }
    }

    /**
     * Available events are: events: create, update, delete, select, pageChanged.
     * The data is in event.detail.
     *
     * @typedef {{
     *     id: {string},
     *     page: {number},
     *     intern: {Object},
     *     text: {string},
     * }} Annotation
     *
     * @param {string} parent   id of the parent element to add the iframe
     * @param {string} viewer   url of the viewer html (source of iframe, without parameter)
     * @param {string} pdf      url of the pdf file to load
     * @param {{viewOnly: bool}} options init pdfjs in view only or not.
     *
     * @return {{
     *   on: {function(string, function(CustomEvent)): void},
     *   off: {function(string, function(CustomEvent)): void},
     *   getAll: {Promise<CustomEvent>},
     *   get: {function(string): Promise<Annotation>},
     *   update: {function(string): Promise},
     *   setAll: {function(Annotation[]): Promise},
     *   add: {function(Annotation): Promise},
     *   delete: {function(string): Promise},
     *   selected: {function(): Promise<Annotation|null>},
     *   select: {function(string): Promise},
     *   currentPage: {function(): Promise<number>},
     *   destroy: {function(): void},
     *   rebuild: {function(): void},
     *   setViewOnly: {function(bool): Promise},
     * }}
     */
    var createPDFJsApi = (parent, viewer, pdf, options = {}) => {
        let currentRequest = Promise.resolve();
        const t = new EventTarget();
        const dispatch = (name, detail = null) => t.dispatchEvent(new CustomEvent(name, {detail}));
        const nextId = ((i = 0) => () => ++i)();
        const pending = {};
        const frame = document.createElement('iframe');
        const ready = (function(){
            const ret = {};
            ret.promise = new Promise(function(resolve, reject){
                ret.resolve = resolve;
                ret.reject = reject;
            });
            return ret;
        })();
        const iframeParams = new URLSearchParams({file: pdf});
        if (options.viewOnly) {
            iframeParams.set('viewOnly', 'yes');
        }
        frame.src = viewer + '?' + iframeParams;
        frame.style.width = '100%';
        frame.style.height = '100%';
        parent.appendChild(frame);

        window.addEventListener('message', dispatchOrRespond);

        return {
            on: t.addEventListener.bind(t),
            off: t.removeEventListener.bind(t),
            getAll: () => requestUnsafe('getAll'),
            get: id => requestUnsafe('get', id),
            update: entry => request('update', entry),
            setAll: newOnes => request('setAll', newOnes),
            add: newOne => request('add', newOne),
            'delete': id => request('delete', id),
            selected: () => requestUnsafe('selected'),
            select: id => requestUnsafe('select', id),
            currentPage: () => requestUnsafe('currentPage'),
            destroy: () => {
                window.removeEventListener('message', dispatchOrRespond);
                iframe.remove();
            },
            rebuild: () => {
                window.addEventListener('message', dispatchOrRespond);
                parent.appendChild(frame);
            },
            setViewOnly: viewOnly => request('viewOnly', viewOnly),
        };

        function request(name, ...args)
        {
            return currentRequest = currentRequest.then(() => requestUnsafe(name, ...args));
        }

        function requestUnsafe(name, ...args)
        {
            return new Promise(ret => {
                const id = nextId();
                pending[id] = ret;
                return ready.promise.then(() => frame.contentWindow.postMessage({id, name, args}));
            });
        }

        function respond(response)
        {
            if(!pending[response.id]){
                return;
            }
            const ret = pending[response.id];
            delete pending[response.id];
            ret(response.value);
        }

        function dispatchOrRespond(event)
        {
            if(event.source !== frame.contentWindow){
                return;
            }

            if(event.data.emit){
                if(event.data.emit.name === 'ready'){
                    ready.resolve();
                }
                dispatch(event.data.emit.name, event.data.emit.detail);
                return;
            }

            if(event.data.response){
                respond(event.data.response);
                return;
            }
        }
    };

    class PdfViewer
    {
      init(id, viewer, url) {
        const element = document.getElementById(id);
        createPDFJsApi(element, viewer, url, {viewOnly: true});
      }
    }

    var contentCss = "/**\n * Style of written contents\n *\n * This file should be identical in the ILIAS plugin, the assessment service and all web apps\n *\n * All styles are defined for a common top element with the class 'xlas-content'\n * The top element can be either the <body> in TinyMCE or a surrounding <div> for content display\n *\n * Font size is set in rem for the top element and in em for sub elements\n *\n * The top element can have an additional class for the used headline scheme:\n * - 'headlines-single' has the same size and no prefix for all headlines\n * - 'headlines-three' has three different sizes for h1, h2 and h3 and no prefix for all headlines\n * - 'headlines-numeric' has the same size a prefix like '1.1.1.1.1.1' for all headlines\n * - 'headlines-edutiek' has the same size and a prefix line 'A.', 'I.', '1.', 'a.', 'aa.', '(1)' for the headlines\n */\n\n.xlas-content {\n    font-family: serif;\n    font-size: 1rem;\n    line-height: 150%;\n    text-align: justify;\n}\n\n.xlas-content p,\n.xlas-content pre {\n    margin-top: 0;\n    margin-bottom: 10px;\n    min-height: 1.5em;\n    text-align: justify;\n}\n\n.xlas-content pre {\n    max-width: 80em;                /* dompdf needs a fixed value */\n    white-space: pre-wrap;          /* keep line breaks and break if needed */\n    word-wrap: break-word;          /* legacy setting (older browsers) */\n    overflow-wrap: break-word;      /* modern variant of word-wrap */\n}\n\n.xlas-content ol,\n.xlas-content ul {\n    margin: 0;\n    padding: 0;\n    margin-bottom: 10px;\n}\n\n.xlas-content li {\n    margin: 0;\n    margin-left: 40px;\n    min-height: 1.5em;\n}\n\n.xlas-content li + li {\n    margin-top: 10px;\n}\n\n.xlas-content li > ol,\n.xlas-content li > ul {\n    margin-top: 10px;\n    margin-bottom: 0;\n}\n\n.xlas-content table {\n    border-collapse: collapse;\n    border: 1px solid gray;\n    width: 100%;\n    table-layout: fixed;\n    margin-bottom: 10px;\n}\n\n.xlas-content tr {\n    vertical-align: top;\n}\n\n.xlas-content td,\n.xlas-content th {\n    border: 1px solid gray;\n    min-width: 1em;\n    min-height: 1em;\n    padding: 5px;\n}\n\n.xlas-content td[data-mce-selected=\"1\"],\n.xlas-content th[data-mce-selected=\"1\"] {\n    border: 2px solid blue;\n}\n\n/**\n * Page break in printing\n */\n.xlas-content hr {\n    page-break-before: always;\n    height: 0;\n    width: 0;\n    border: 0;\n    margin: 0;\n}\n\n/**\n * Page break in tiny\n */\n.xlas-content .mce-pagebreak {\n    width: calc(100% + 40px);\n    height: 1px;\n    height: 20px;\n    background-color: #eeeeee;\n    border-top: 1px solid #ccc;\n    margin-left: -20px;\n}\n\n/**\n * Headlines in general\n */\n\n.xlas-content {\n    counter-reset: xlas-h1 xlas-h2 xlas-h3 xlas-h4 xlas-h5 xlas-h6;\n}\n\n.xlas-content h1,\n.xlas-content h2,\n.xlas-content h3,\n.xlas-content h4,\n.xlas-content h5,\n.xlas-content h6 {\n    font-family: serif;\n    font-size: 1em;\n    font-weight: bold;\n    padding: 0;\n    margin-top: 0;\n    margin-bottom: 10px;\n    min-height: 1.5em;\n}\n\n.xlas-content h1 {\n    counter-increment: xlas-h1;\n    counter-reset: xlas-h2 xlas-h3 xlas-h4 xlas-h5 xlas-h6;\n}\n\n.xlas-content h2 {\n    counter-increment: xlas-h2;\n    counter-reset: xlas-h3 xlas-h4 xlas-h5 xlas-h6;\n}\n\n.xlas-content h3 {\n    counter-increment: xlas-h3;\n    counter-reset: xlas-h4 xlas-h5 xlas-h6;\n}\n\n.xlas-content h4 {\n    counter-increment: xlas-h4;\n    counter-reset: xlas-h5 xlas-h6;\n}\n\n.xlas-content h5 {\n    counter-increment: xlas-h5;\n    counter-reset: xlas-h6;\n}\n\n.xlas-content h6 {\n    counter-increment: xlas-h6;\n}\n\n/**\n * Three level headline style\n */\n\n.xlas-content.headlines-three h1 {\n    font-size: 1.3em !important;\n}\n\n.xlas-content.headlines-three h2 {\n    font-size: 1.15em !important;\n}\n\n.xlas-content.headlines-three h3 {\n    font-size: 1.0em !important;\n}\n\n/**\n * Numeric headline style\n */\n\n.xlas-content.headlines-numeric h1::before {\n    content: counter(xlas-h1, decimal) \" \";\n}\n\n.xlas-content.headlines-numeric h2::before {\n    content: counter(xlas-h1, decimal) \".\" counter(xlas-h2, decimal) \" \";\n}\n\n.xlas-content.headlines-numeric h3::before {\n    content: counter(xlas-h1, decimal) \".\" counter(xlas-h2, decimal) \".\" counter(xlas-h3, decimal) \" \";\n}\n\n.xlas-content.headlines-numeric h4::before {\n    content: counter(xlas-h1, decimal) \".\" counter(xlas-h2, decimal) \".\" counter(xlas-h3, decimal) \".\" counter(xlas-h4, decimal) \" \";\n}\n\n.xlas-content.headlines-numeric h5::before {\n    content: counter(xlas-h1, decimal) \".\" counter(xlas-h2, decimal) \".\" counter(xlas-h3, decimal) \".\" counter(xlas-h4, decimal) \".\" counter(xlas-h5, decimal) \" \";\n}\n\n.xlas-content.headlines-numeric h6::before {\n    content: counter(xlas-h1, decimal) \".\" counter(xlas-h2, decimal) \".\" counter(xlas-h3, decimal) \".\" counter(xlas-h4, decimal) \".\" counter(xlas-h5, decimal) \".\" counter(xlas-h6, decimal) \" \";\n}\n\n/**\n * Edutiek headline style\n */\n\n.xlas-content.headlines-edutiek h1::before {\n    content: counter(xlas-h1, upper-latin) \". \";\n}\n\n.xlas-content.headlines-edutiek h2::before {\n    content: counter(xlas-h2, upper-roman) \". \";\n}\n\n.xlas-content.headlines-edutiek h3::before {\n    content: counter(xlas-h3, decimal) \". \";\n}\n\n.xlas-content.headlines-edutiek h4::before {\n    content: counter(xlas-h4, lower-latin) \". \";\n}\n\n.xlas-content.headlines-edutiek h5::before {\n    content: counter(xlas-h5, lower-latin) counter(xlas-h5, lower-latin) \". \";\n}\n\n.xlas-content.headlines-edutiek h6::before {\n    content: \"(\" counter(xlas-h6, decimal) \") \";\n}\n";

    var tinyTexts = {
        "settingsHeadings": "Überschriften",
        "settingsHeading": "Überschrift",
        "settingsHeading1": "Überschrift 1",
        "settingsHeading2": "Überschrift 2",
        "settingsHeading3": "Überschrift 3",
        "settingsHeading4": "Überschrift 4",
        "settingsHeading5": "Überschrift 5",
        "settingsHeading6": "Überschrift 6",
        "settingsInline": "Zeichenformate",
        "settingsBold": "Fett",
        "settingsItalic": "Kursiv",
        "settingsUnderline": "Unterstrichen",
        "settingsStrikethrough": "Durchgestrichen",
        "settingsSuperscript": "Hochgestellt",
        "settingsSubscript": "Tiefgestellt",
        "settingsCode": "Code",
        "settingsListElement": "Listenelement",
        "settingsBlocks": "Absatzformate",
        "settingsParagraph": "Absatz",
        "settingsBlockquote": "Zitat",
        "settingsPre": "Maschinenschrift",
        "settingsAlign": "Ausrichtung",
        "settingsAlignLeft": "Linksbündig",
        "settingsAlignCenter": "Zentriert",
        "settingsAlignRight": "Rechtsbündig",
        "settingsAlignJustify": "Blocksatz",
        "tinyHelperIframeAriaText": "Editor",
        "loadDraft": "In Ihrem Browser sind ungespeicherte Änderungen zu \"%title%\" gespeichert. Möchten Sie diese laden?"
    };

    var tinyDE = {
        "#": "#",
        "Accessibility": "Barrierefreiheit",
        "Accordion": "",
        "Accordion body...": "",
        "Accordion summary...": "",
        "Action": "Aktion",
        "Activity": "Aktivit\xe4t",
        "Address": "Adresse",
        "Advanced": "Erweitert",
        "Align": "Ausrichtung",
        "Align center": "Zentrieren",
        "Align left": "Linksb\xfcndig ausrichten",
        "Align right": "Rechtsb\xfcndig ausrichten",
        "Alignment": "Ausrichtung",
        "Alignment {0}": "",
        "All": "Alle",
        "Alternative description": "Alternative Beschreibung",
        "Alternative source": "Alternative Quelle",
        "Alternative source URL": "URL der alternativen Quelle",
        "Anchor": "Anker",
        "Anchor...": "Textmarke",
        "Anchors": "Anker",
        "Animals and Nature": "Tiere und Natur",
        "Arrows": "Pfeile",
        "B": "B",
        "Background color": "Hintergrundfarbe",
        "Background color {0}": "",
        "Black": "Schwarz",
        "Block": "Blocksatz",
        "Block {0}": "",
        "Blockquote": "Blockzitat",
        "Blocks": "Bl\xf6cke",
        "Blue": "Blau",
        "Blue component": "Blauanteil",
        "Body": "Inhalt",
        "Bold": "Fett",
        "Border": "Rahmen",
        "Border color": "Rahmenfarbe",
        "Border style": "Rahmenstil",
        "Border width": "Rahmenbreite",
        "Bottom": "Unten",
        "Browse files": "",
        "Browse for an image": "Bild...",
        "Browse links": "",
        "Bullet list": "Aufz\xe4hlung",
        "Cancel": "Abbrechen",
        "Caption": "Beschriftung",
        "Cell": "Zelle",
        "Cell padding": "Zelleninnenabstand",
        "Cell properties": "Zelleigenschaften",
        "Cell spacing": "Zellenabstand",
        "Cell styles": "Zellstil",
        "Cell type": "Zelltyp",
        "Center": "Zentriert",
        "Characters": "Zeichen (mit Leerzeichen)",
        "Characters (no spaces)": "Zeichen (ohne Leerzeichen)",
        "Circle": "Kreis",
        "Class": "Klasse",
        "Clear formatting": "Formatierung entfernen",
        "Close": "Schlie\xdfen",
        "Code": "Code",
        "Code sample...": "Codebeispiel...",
        "Code view": "Code Ansicht",
        "Color Picker": "Farbwahl",
        "Color swatch": "Farbpalette",
        "Cols": "Spalten",
        "Column": "Spalte",
        "Column clipboard actions": "Spalten-Zwischenablage-Aktionen",
        "Column group": "Spaltengruppe",
        "Column header": "Spaltenkopf",
        "Constrain proportions": "Seitenverh\xe4ltnis beibehalten",
        "Copy": "Kopieren",
        "Copy column": "Spalte kopieren",
        "Copy row": "Zeile kopieren",
        "Could not find the specified string.": "Die angegebene Zeichenfolge wurde nicht gefunden.",
        "Could not load emojis": "Emojis konnten nicht geladen werden",
        "Count": "Anzahl",
        "Currency": "W\xe4hrung",
        "Current window": "Aktuelles Fenster",
        "Custom color": "Benutzerdefinierte Farbe",
        "Custom...": "Benutzerdefiniert...",
        "Cut": "Ausschneiden",
        "Cut column": "Spalte ausschneiden",
        "Cut row": "Zeile ausschneiden",
        "Dark Blue": "Dunkelblau",
        "Dark Gray": "Dunkelgrau",
        "Dark Green": "Dunkelgr\xfcn",
        "Dark Orange": "Dunkelorange",
        "Dark Purple": "Dunkellila",
        "Dark Red": "Dunkelrot",
        "Dark Turquoise": "Dunkelt\xfcrkis",
        "Dark Yellow": "Dunkelgelb",
        "Dashed": "Gestrichelt",
        "Date/time": "Datum/Uhrzeit",
        "Decrease indent": "Einzug verkleinern",
        "Default": "Standard",
        "Delete accordion": "",
        "Delete column": "Spalte l\xf6schen",
        "Delete row": "Zeile l\xf6schen",
        "Delete table": "Tabelle l\xf6schen",
        "Dimensions": "Abmessungen",
        "Disc": "Scheibe",
        "Div": "Div",
        "Document": "Dokument",
        "Dotted": "Gepunktet",
        "Double": "Doppelt",
        "Drop an image here": "Bild hier ablegen",
        "Dropped file type is not supported": "Hereingezogener Dateityp wird nicht unterst\xfctzt",
        "Edit": "Bearbeiten",
        "Embed": "Einbettung",
        "Emojis": "Emojis",
        "Emojis...": "Emojis...",
        "Error": "Fehler",
        "Error: Form submit field collision.": "Fehler: Kollision der Formularbest\xe4tigungsfelder.",
        "Error: No form element found.": "Fehler: Kein Formularelement gefunden.",
        "Extended Latin": "Erweitertes Latein",
        "Failed to initialize plugin: {0}": "Plugin konnte nicht initialisiert werden: {0}",
        "Failed to load plugin url: {0}": "Plugin-URL konnte nicht geladen werden: {0}",
        "Failed to load plugin: {0} from url {1}": "Plugin konnte nicht geladen werden: {0} von URL {1}",
        "Failed to upload image: {0}": "Bild konnte nicht hochgeladen werden: {0}",
        "File": "Datei",
        "Find": "Suchen",
        "Find (if searchreplace plugin activated)": "Suchen (wenn Suchen/Ersetzen-Plugin aktiviert ist)",
        "Find and Replace": "Suchen und Ersetzen",
        "Find and replace...": "Suchen und ersetzen...",
        "Find in selection": "In Auswahl suchen",
        "Find whole words only": "Nur ganze W\xf6rter suchen",
        "Flags": "Flaggen",
        "Focus to contextual toolbar": "Fokus auf kontextbezogene Symbolleiste",
        "Focus to element path": "Fokus auf Elementpfad",
        "Focus to menubar": "Fokus auf Men\xfcleiste",
        "Focus to toolbar": "Fokus auf Symbolleiste",
        "Font": "Schriftart",
        "Font size {0}": "",
        "Font sizes": "Schriftgr\xf6\xdfen",
        "Font {0}": "",
        "Fonts": "Schriftarten",
        "Food and Drink": "Essen und Trinken",
        "Footer": "Fu\xdfzeile",
        "Format": "Format",
        "Format {0}": "",
        "Formats": "Formate",
        "Fullscreen": "Vollbild",
        "G": "G",
        "General": "Allgemein",
        "Gray": "Grau",
        "Green": "Gr\xfcn",
        "Green component": "Gr\xfcnanteil",
        "Groove": "Gekantet",
        "Handy Shortcuts": "Praktische Tastenkombinationen",
        "Header": "Kopfzeile",
        "Header cell": "Kopfzelle",
        "Heading 1": "\xdcberschrift 1",
        "Heading 2": "\xdcberschrift 2",
        "Heading 3": "\xdcberschrift 3",
        "Heading 4": "\xdcberschrift 4",
        "Heading 5": "\xdcberschrift 5",
        "Heading 6": "\xdcberschrift 6",
        "Headings": "\xdcberschriften",
        "Height": "H\xf6he",
        "Help": "Hilfe",
        "Hex color code": "Hexadezimal-Farbwert",
        "Hidden": "Unsichtbar",
        "Horizontal align": "Horizontal ausrichten",
        "Horizontal line": "Horizontale Linie",
        "Horizontal space": "Horizontaler Raum",
        "ID": "ID",
        "ID should start with a letter, followed only by letters, numbers, dashes, dots, colons or underscores.": "Die ID muss mit einem Buchstaben beginnen gefolgt von Buchstaben, Zahlen, Bindestrichen, Punkten, Doppelpunkten oder Unterstrichen.",
        "Image is decorative": "Bild ist dekorativ",
        "Image list": "Bildliste",
        "Image title": "Bildtitel",
        "Image...": "Bild...",
        "ImageProxy HTTP error: Could not find Image Proxy": "Image Proxy HTTP Fehler: Kann Image Proxy nicht finden",
        "ImageProxy HTTP error: Incorrect Image Proxy URL": "Image Proxy HTTP Fehler: Falsche Image Proxy URL",
        "ImageProxy HTTP error: Rejected request": "Image Proxy HTTP Fehler: Abgewiesene Anfrage",
        "ImageProxy HTTP error: Unknown ImageProxy error": "Image Proxy HTTP Fehler: Unbekannter Image Proxy Fehler",
        "Increase indent": "Einzug vergr\xf6\xdfern",
        "Inline": "Zeichenformate",
        "Insert": "Einf\xfcgen",
        "Insert Template": "Vorlage einf\xfcgen",
        "Insert accordion": "",
        "Insert column after": "Neue Spalte danach einf\xfcgen",
        "Insert column before": "Neue Spalte davor einf\xfcgen",
        "Insert date/time": "Datum/Uhrzeit einf\xfcgen",
        "Insert image": "Bild einf\xfcgen",
        "Insert link (if link plugin activated)": "Link einf\xfcgen (wenn Link-Plugin aktiviert ist)",
        "Insert row after": "Neue Zeile danach einf\xfcgen",
        "Insert row before": "Neue Zeile davor einf\xfcgen",
        "Insert table": "Tabelle einf\xfcgen",
        "Insert template...": "Vorlage einf\xfcgen...",
        "Insert video": "Video einf\xfcgen",
        "Insert/Edit code sample": "Codebeispiel einf\xfcgen/bearbeiten",
        "Insert/edit image": "Bild einf\xfcgen/bearbeiten",
        "Insert/edit link": "Link einf\xfcgen/bearbeiten",
        "Insert/edit media": "Medien einf\xfcgen/bearbeiten",
        "Insert/edit video": "Video einf\xfcgen/bearbeiten",
        "Inset": "Eingelassen",
        "Invalid hex color code: {0}": "Ung\xfcltiger Hexadezimal-Farbwert: {0}",
        "Invalid input": "Ung\xfcltige Eingabe",
        "Italic": "Kursiv",
        "Justify": "Blocksatz",
        "Keyboard Navigation": "Tastaturnavigation",
        "Language": "Sprache",
        "Learn more...": "Erfahren Sie mehr dazu...",
        "Left": "Links",
        "Left to right": "Von links nach rechts",
        "Light Blue": "Hellblau",
        "Light Gray": "Hellgrau",
        "Light Green": "Hellgr\xfcn",
        "Light Purple": "Helllila",
        "Light Red": "Hellrot",
        "Light Yellow": "Hellgelb",
        "Line height": "Liniendicke",
        "Link list": "Linkliste",
        "Link...": "Link...",
        "List Properties": "Liste Eigenschaften",
        "List properties...": "Liste Eigenschaften",
        "Loading emojis...": "Lade Emojis...",
        "Loading...": "Wird geladen...",
        "Lower Alpha": "Lateinisches Alphabet in Kleinbuchstaben",
        "Lower Greek": "Griechische Kleinbuchstaben",
        "Lower Roman": "Kleiner r\xf6mischer Buchstabe",
        "Match case": "Gro\xdf-/Kleinschreibung beachten",
        "Mathematical": "Mathematisch",
        "Media poster (Image URL)": "Medienposter (Bild-URL)",
        "Media...": "Medien...",
        "Medium Blue": "Mittleres Blau",
        "Medium Gray": "Mittelgrau",
        "Medium Purple": "Mittelviolett",
        "Merge cells": "Zellen verbinden",
        "Middle": "Mitte",
        "Midnight Blue": "Mitternachtsblau",
        "More...": "Mehr...",
        "Name": "Name",
        "Navy Blue": "Marineblau",
        "New document": "Neues Dokument",
        "New window": "Neues Fenster",
        "Next": "N\xe4chste",
        "No": "Nein",
        "No alignment": "Keine Ausrichtung",
        "No color": "Keine Farbe",
        "Nonbreaking space": "Gesch\xfctztes Leerzeichen",
        "None": "Keine",
        "Numbered list": "Nummerierte Liste",
        "OR": "ODER",
        "Objects": "Objekte",
        "Ok": "Ok",
        "Open help dialog": "Hilfe-Dialog \xf6ffnen",
        "Open link": "Link \xf6ffnen",
        "Open link in...": "Link \xf6ffnen in...",
        "Open popup menu for split buttons": "\xd6ffne Popup Menge um Buttons zu trennen",
        "Orange": "Orange",
        "Outset": "Hervorstehend",
        "Page break": "Seitenumbruch",
        "Paragraph": "Absatz",
        "Paste": "Einf\xfcgen",
        "Paste as text": "Als Text einf\xfcgen",
        "Paste column after": "Spalte danach einf\xfcgen",
        "Paste column before": "Spalte davor einf\xfcgen",
        "Paste is now in plain text mode. Contents will now be pasted as plain text until you toggle this option off.": "Einf\xfcgen ist nun im unformatierten Textmodus. Inhalte werden ab jetzt als unformatierter Text eingef\xfcgt, bis Sie diese Einstellung wieder deaktivieren.",
        "Paste or type a link": "Link einf\xfcgen oder eingeben",
        "Paste row after": "Zeile danach einf\xfcgen",
        "Paste row before": "Zeile davor einf\xfcgen",
        "Paste your embed code below:": "F\xfcgen Sie Ihren Einbettungscode unten ein:",
        "People": "Menschen",
        "Plugins": "Plugins",
        "Plugins installed ({0}):": "Installierte Plugins ({0}):",
        "Powered by {0}": "Betrieben von {0}",
        "Pre": "Pre",
        "Preferences": "Einstellungen",
        "Preformatted": "Vorformatiert",
        "Premium plugins:": "Premium-Plugins:",
        "Press the Up and Down arrow keys to resize the editor.": "",
        "Press the arrow keys to resize the editor.": "",
        "Press {0} for help": "",
        "Preview": "Vorschau",
        "Previous": "Vorherige",
        "Print": "Drucken",
        "Print...": "Drucken...",
        "Purple": "Violett",
        "Quotations": "Anf\xfchrungszeichen",
        "R": "R",
        "Range 0 to 255": "Spanne 0 bis 255",
        "Red": "Rot",
        "Red component": "Rotanteil",
        "Redo": "Wiederholen",
        "Remove": "Entfernen",
        "Remove color": "Farbauswahl aufheben",
        "Remove link": "Link entfernen",
        "Replace": "Ersetzen",
        "Replace all": "Alle ersetzen",
        "Replace with": "Ersetzen durch",
        "Resize": "Skalieren",
        "Restore last draft": "Letzten Entwurf wiederherstellen",
        "Reveal or hide additional toolbar items": "",
        "Rich Text Area": "Rich-Text-Area",
        "Rich Text Area. Press ALT-0 for help.": "Rich-Text-Bereich. Dr\xfccken Sie Alt+0 f\xfcr Hilfe.",
        "Rich Text Area. Press ALT-F9 for menu. Press ALT-F10 for toolbar. Press ALT-0 for help": "Rich-Text-Bereich. Dr\xfccken Sie Alt+F9 f\xfcr das Men\xfc. Dr\xfccken Sie Alt+F10 f\xfcr die Symbolleiste. Dr\xfccken Sie Alt+0 f\xfcr Hilfe.",
        "Ridge": "Eingeritzt",
        "Right": "Rechts",
        "Right to left": "Von rechts nach links",
        "Row": "Zeile",
        "Row clipboard actions": "Zeilen-Zwischenablage-Aktionen",
        "Row group": "Zeilengruppe",
        "Row header": "Zeilenkopf",
        "Row properties": "Zeileneigenschaften",
        "Row type": "Zeilentyp",
        "Rows": "Zeilen",
        "Save": "Speichern",
        "Save (if save plugin activated)": "Speichern (wenn Save-Plugin aktiviert ist)",
        "Scope": "Bereich",
        "Search": "Suchen",
        "Select all": "Alles ausw\xe4hlen",
        "Select...": "Auswahl...",
        "Selection": "Auswahl",
        "Shortcut": "Tastenkombination",
        "Show blocks": "Bl\xf6cke anzeigen",
        "Show caption": "Beschriftung anzeigen",
        "Show invisible characters": "Unsichtbare Zeichen anzeigen",
        "Size": "Schriftgr\xf6\xdfe",
        "Solid": "Durchgezogen",
        "Source": "Quelle",
        "Source code": "Quellcode",
        "Special Character": "Sonderzeichen",
        "Special character...": "Sonderzeichen...",
        "Split cell": "Zelle aufteilen",
        "Square": "Rechteck",
        "Start list at number": "Beginne Liste mit Nummer",
        "Strikethrough": "Durchgestrichen",
        "Style": "Formatvorlage",
        "Subscript": "Tiefgestellt",
        "Superscript": "Hochgestellt",
        "Switch to or from fullscreen mode": "Vollbildmodus umschalten",
        "Symbols": "Symbole",
        "System Font": "Betriebssystemschriftart",
        "Table": "Tabelle",
        "Table caption": "Tabellenbeschriftung",
        "Table properties": "Tabelleneigenschaften",
        "Table styles": "Tabellenstil",
        "Template": "Vorlage",
        "Templates": "Vorlagen",
        "Text": "Text",
        "Text color": "Textfarbe",
        "Text color {0}": "",
        "Text to display": "Anzuzeigender Text",
        "The URL you entered seems to be an email address. Do you want to add the required mailto: prefix?": "Diese URL scheint eine E-Mail-Adresse zu sein. M\xf6chten Sie das dazu ben\xf6tigte mailto: voranstellen?",
        "The URL you entered seems to be an external link. Do you want to add the required http:// prefix?": "Diese URL scheint ein externer Link zu sein. M\xf6chten Sie das dazu ben\xf6tigte http:// voranstellen?",
        "The URL you entered seems to be an external link. Do you want to add the required https:// prefix?": "Die eingegebene URL scheint ein externer Link zu sein. Soll das fehlende https:// davor erg\xe4nzt werden?",
        "Title": "Titel",
        "To open the popup, press Shift+Enter": "Dr\xfccken Sie Umschalt+Eingabe, um das Popup-Fenster zu \xf6ffnen.",
        "Toggle accordion": "",
        "Tools": "Werkzeuge",
        "Top": "Oben",
        "Travel and Places": "Reisen und Orte",
        "Turquoise": "T\xfcrkis",
        "Underline": "Unterstrichen",
        "Undo": "R\xfcckg\xe4ngig machen",
        "Upload": "Hochladen",
        "Uploading image": "Bild wird hochgeladen",
        "Upper Alpha": "Lateinisches Alphabet in Gro\xdfbuchstaben",
        "Upper Roman": "Gro\xdfer r\xf6mischer Buchstabe",
        "Url": "URL",
        "User Defined": "Benutzerdefiniert",
        "Valid": "G\xfcltig",
        "Version": "Version",
        "Vertical align": "Vertikal ausrichten",
        "Vertical space": "Vertikaler Raum",
        "View": "Ansicht",
        "Visual aids": "Visuelle Hilfen",
        "Warn": "Warnung",
        "White": "Wei\xdf",
        "Width": "Breite",
        "Word count": "Anzahl der W\xf6rter",
        "Words": "W\xf6rter",
        "Words: {0}": "Wortzahl: {0}",
        "Yellow": "Gelb",
        "Yes": "Ja",
        "You are using {0}": "Sie verwenden {0}",
        "You have unsaved changes are you sure you want to navigate away?": "Die \xc4nderungen wurden noch nicht gespeichert. Sind Sie sicher, dass Sie diese Seite verlassen wollen?",
        "Your browser doesn't support direct access to the clipboard. Please use the Ctrl+X/C/V keyboard shortcuts instead.": "Ihr Browser unterst\xfctzt leider keinen direkten Zugriff auf die Zwischenablage. Bitte benutzen Sie die Tastenkombinationen Strg+X/C/V.",
        "alignment": "Ausrichtung",
        "austral sign": "Australzeichen",
        "cedi sign": "Cedizeichen",
        "colon sign": "Doppelpunkt",
        "cruzeiro sign": "Cruzeirozeichen",
        "currency sign": "W\xe4hrungssymbol",
        "dollar sign": "Dollarzeichen",
        "dong sign": "Dongzeichen",
        "drachma sign": "Drachmezeichen",
        "euro-currency sign": "Eurozeichen",
        "example": "Beispiel",
        "formatting": "Formatierung",
        "french franc sign": "Franczeichen",
        "german penny symbol": "Pfennigzeichen",
        "guarani sign": "Guaranizeichen",
        "history": "Historie",
        "hryvnia sign": "Hrywnjazeichen",
        "indentation": "Einr\xfcckungen",
        "indian rupee sign": "Indisches Rupiezeichen",
        "kip sign": "Kipzeichen",
        "lira sign": "Lirezeichen",
        "livre tournois sign": "Livrezeichen",
        "manat sign": "Manatzeichen",
        "mill sign": "Millzeichen",
        "naira sign": "Nairazeichen",
        "new sheqel sign": "Schekelzeichen",
        "nordic mark sign": "Zeichen nordische Mark",
        "peseta sign": "Pesetazeichen",
        "peso sign": "Pesozeichen",
        "ruble sign": "Rubelzeichen",
        "rupee sign": "Rupiezeichen",
        "spesmilo sign": "Spesmilozeichen",
        "styles": "Stile",
        "tenge sign": "Tengezeichen",
        "tugrik sign": "Tugrikzeichen",
        "turkish lira sign": "T\xfcrkisches Lirazeichen",
        "won sign": "Wonzeichen",
        "yen character": "Yenzeichen",
        "yen/yuan character variant one": "Yen-/Yuanzeichen Variante 1",
        "yuan character": "Yuanzeichen",
        "yuan character, in hong kong and taiwan": "Yuanzeichen in Hongkong und Taiwan",
        "{0} characters": "{0}\xa0Zeichen",
        "{0} columns, {1} rows": "",
        "{0} words": "{0} W\xf6rter"
    };

    /**
     * Get a named text
     * @param {string }key
     * @returns {string}
     */
    function t(key) {
        return tinyTexts[key] ?? key;
    }

    class TinyHelper
    {
        /**
         * Init the TinyMce editor for a textare element
         *
         * @param {string} id
         * @param {string} lang
         * @param {string} formatting_options
         * @param {string} headline_scheme
         */
        init(id, lang = 'de', formatting_options = 'extended', headline_scheme = 'three')
        {
            const url = new URL(window.location.toLocaleString()).searchParams;
            const name = document.querySelector('textarea#' + id).name;
            const storageKey = 'xlas_tiny_' + url.get('cmdNode') + '_' + url.get('ref_id') + '_' + name;

            tinymce.addI18n("de", tinyDE);

            tinymce.init({
                license_key: 'gpl',
                language: lang,
                selector: 'textarea#' + id,
                menubar: false,
                statusbar: true,
                branding: false,
                elementpath: false,
                body_class: 'xlas-content ' + this.contentClass(headline_scheme),     // used by content.css
                plugins: 'lists charmap wordcount table pagebreak',
                toolbar: this.tinyToolbar(formatting_options),
                toolbar_mode: 'wrap',
                valid_elements: this.tinyValidElements(formatting_options),
                valid_styles: this.tinyValidStyles(formatting_options),
                formats: this.tinyFormats(),
                style_formats: this.tinyStyleFormats(formatting_options, headline_scheme),
                text_patterns: false,
                content_style: contentCss,
                browser_spellcheck: true,
                highlight_on_focus: true,
                iframe_aria_text: t('tinyHelperIframeAriaText'),
                paste_as_text: false,         // keep formats when copying between clipboards
                paste_block_drop: true,       // prevent unfiltered content from drag & drop
                paste_merge_formats: true,    // default
                paste_tab_spaces: 4,          // default
                smart_paste: false,           // don't create hyperlinks automatically
                setup: (editor) => {
                    editor.on('init', () => {this.handleDraft(id, storageKey, editor);});
                },
                paste_data_images: false,     // don't paste images
                paste_remove_styles_if_webkit: true,  // default
                paste_webkit_styles: 'none',          // default
                table_appearance_options: false,
                table_advtab: false,
                table_cell_advtab: false,
                table_row_advtab: false,
                table_sizing_mode: 'responsive',
                table_default_styles: {},         // no inline styles on new tables
                table_default_attributes: {},      // no default attributes like width
                table_resize_bars: false,
                table_toolbar: 'tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol | tablecellbackgroundcolor',
                pagebreak_separator: '<hr>',
                pagebreak_split_block: true,  // ensures clean split, important for xsl in backend
            });
        }

        tinyToolbar(formatting_options) {
            switch (formatting_options) {
                case 'extended':
                    return 'undo redo styles bold italic underline bullist numlist indent outdent forecolor backcolor removeformat charmap table pagebreak wordcount'
                case 'full':
                    return 'undo redo styles bold italic underline bullist numlist removeformat charmap wordcount';
                case 'medium':
                    return 'undo redo bold italic underline bullist numlistremoveformat charmap wordcount';
                case 'minimal':
                    return 'undo redo bold italic underline removeformat charmap wordcount';
                case 'none':
                default:
                    return 'undo redo charmap wordcount';
            }
        }

        /**
         * @see https://www.tiny.cloud/docs/configure/content-filtering/#valid_elements
         */
        tinyValidElements(formatting_options) {
            switch (formatting_options) {
                case 'extended':
                    return '@[style|border|colspan|rowspan],'
                        + 'p/div,br,strong/b,em/i,u,s,ol,ul,li,h1,h2,h3,h4,h5,h6,pre,code,blockquote,span,sub,sup,table,thead,tbody,th,tr,td,hr,'
                        + 'img[class<mce-pagebreak|src|data-mce-resize|data-mce-placeholder|data-mce-selected]';
                case 'full':
                    return 'p/div,br,strong/b,em/i,u,ol,ul,li,h1,h2,h3,h4,h5,h6,pre';
                case 'medium':
                    return 'p/div,br,strong/b,em/i,u,ol,ul,li';
                case 'minimal':
                    return 'p/div,p/li,br,strong/b,em/i,u';
                case 'none':
                default:
                    return 'p/div,p/li,br';
            }
        }

        tinyValidStyles(formatting_options) {
            switch (formatting_options) {
                case 'extended':
                    return {
                        '*': 'background-color,background,color,text-align,mce-pagebreak,padding-left'
                    };
                default:
                    return {};
            }
        }

        tinyStyleFormats(formatting_options, headline_scheme) {

            const headings = {title: t('settingsHeadings'), items: [] };
            switch (headline_scheme) {
                case 'single':
                    headings.items = [
                        { title: t('settingsHeadings'), format: 'h1' },
                    ];
                    break;
                case 'three':
                    headings.items = [
                        { title: t('settingsHeading1'), format: 'h1' },
                        { title: t('settingsHeading2'), format: 'h2' },
                        { title: t('settingsHeading3'), format: 'h3' },
                    ];
                    break;
                default:
                    headings.items = [
                        { title: t('settingsHeading1'), format: 'h1' },
                        { title: t('settingsHeading2'), format: 'h2' },
                        { title: t('settingsHeading3'), format: 'h3' },
                        { title: t('settingsHeading4'), format: 'h4' },
                        { title: t('settingsHeading5'), format: 'h5' },
                        { title: t('settingsHeading6'), format: 'h6' },
                    ];
            }

            const inline = {title: t('settingsInline'), items: [] };
            switch (formatting_options) {
                case 'extended':
                    inline.items = [
                        { title: t('settingsBold'), format: 'bold' },
                        { title: t('settingsItalic'), format: 'italic' },
                        { title: t('settingsUnderline'), format: 'underline' },
                        { title: t('settingsStrikethrough'), format: 'strikethrough' },
                        { title: t('settingsSuperscript'), format: 'superscript' },
                        { title: t('settingsSubscript'), format: 'subscript' },
                        { title: t('settingsCode'), format: 'code' }
                    ];
                    break;
                case 'full':
                case 'medium':
                case 'minimal':
                    inline.items = [
                        { title: t('settingsBold'), format: 'bold' },
                        { title: t('settingsItalic'), format: 'italic' },
                        { title: t('settingsUnderline'), format: 'underline' },
                    ];
                    break;
            }

            const blocks = { title: t('settingsBlocks'), items: [] };
            switch (formatting_options) {
                case 'extended':
                    blocks.items = [
                        { title: t('settingsParagraph'), format: 'p' },
                        { title: t('settingsBlockquote'), format: 'blockquote' },
                        { title: t('settingsPre'), format: 'pre' },
                    ];
                    break;
                case 'full':
                    blocks.items = [
                        { title: t('settingsParagraph'), format: 'p' },
                        { title: t('settingsPre'), format: 'pre' },
                    ];
                    break;
                case 'medium':
                case 'minimal':
                    blocks.items = [
                        { title: t('settingsParagraph'), format: 'p' },
                    ];
                    break;
            }

            const align = { title: t('settingsAlign'), items: [] };
            switch (formatting_options) {
                case 'extended':
                    align.items = [
                        { title: t('settingsAlignLeft'), format: 'alignleft' },
                        { title: t('settingsAlignCenter'), format: 'aligncenter' },
                        { title: t('settingsAlignRight'), format: 'alignright' },
                        { title: t('settingsAlignJustify'), format: 'alignjustify' }
                    ];
                    break;
            }

            const formats = [];
            if (headings.items.length) {
                formats.push(headings);
            }
            if (inline.items.length) {
                formats.push(inline);
            }
            if (blocks.items.length) {
                formats.push(blocks);
            }
            if (align.items.length) {
                formats.push(align);
            }

            return formats;
        }

        /**
         * @see https://www.tiny.cloud/docs/configure/content-formatting/#formats
         */
        tinyFormats() {
            return {
                underline: { inline: 'u', remove: 'all' },
                strikethrough: { inline: 's', remove: 'all' }
            }
        }

        contentClass(headline_scheme) {
            switch (headline_scheme) {
                case 'single':
                    return 'headlines-single';
                case 'three':
                    return 'headlines-three';
                case 'numeric':
                    return 'headlines-numeric';
                case 'edutiek':
                    return 'headlines-edutiek';
                default:
                    return '';
            }
        }

        saveDraft (storageKey, editor){
            localStorage.setItem(storageKey, editor.getContent({ format: 'html' }));
        };

        handleDraft(id, storageKey, editor){
            const stored = localStorage.getItem(storageKey);
            const title = document.querySelector(`label[for="${id}"]`).textContent;
            if (stored && stored !== editor.getContent()) {
                const loadDraft = window.confirm(t('loadDraft').replace('%title%', title));

                if (loadDraft) {
                    editor.setContent(stored);
                } else {
                    localStorage.removeItem(storageKey);
                }
            }
            editor.on('change input undo redo setcontent blur', () => {this.saveDraft(storageKey, editor);});
        }
    }

    /**
     * Adapt the behavior of the ILIAS tools
     */
    class ToolsHandler {

        /**
         * Updates the tools engagement state in cookies to mark tools as not engaged.
         *
         * @param {Array} tools - An array of tool identifiers that should be marked as disengaged. Each tool
         *                        identifier is used to update its corresponding state in the "tools" array.
         * @return {void}
         */
        closeTools(tools) {
            document.cookie.split(';').forEach(cookie => {
                const entry = cookie.trim().split('=');
                let value;
                try {
                    value = JSON.parse(entry[1]);
                } catch (error) {
                    return;
                }
                if (Reflect.has(value || {}, 'tools_engaged')) {
                    value.tools_engaged = false;
                    value.any_entry_engaged = false;
                    value.known_tools = tools;
                    tools.forEach((tool, i) => {
                        // tools are "compressed" as an array of [<removable>, <engaged>, <hidden>, <position>]
                        // We only whant to set <engaged> to 0, but if the tool hasn't been set, we need to add it.
                        value.tools[tool] = value.tools[tool] || [0, 0, 0, 'T:' + i];
                        value.tools[tool][1] = 0;
                    });
                    document.cookie = entry[0] + '=' + JSON.stringify(value);
                }
            });
        }
    };

    il.Xlas = il.Xlas || {};
    il.Xlas.Fixation = il.Xlas.Fixation || new Fixation();
    il.Xlas.PdfViewer = il.Xlas.PdfViewer || new PdfViewer();
    il.Xlas.TinyHelper = il.Xlas.TinyHelper || new TinyHelper();
    il.Xlas.ToolsHandler = il.Xlas.ToolsHandler || new ToolsHandler();

})(il);
