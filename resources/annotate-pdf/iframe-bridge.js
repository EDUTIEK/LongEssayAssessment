run();

function run()
{
    window.localStorage.removeItem('pdfjs.history');
    window.localStorage.removeItem('pdfjs.preferences');
    setup(forwardEvent, actions => {
        window.addEventListener('message', event => {
            then(
                actions[event.data.name](...event.data.args),
                value => window.parent.postMessage({response: {id: event.data.id, value}})
            );
        });
    });
    
    window.addEventListener('beforeunload', () => {
        PDFViewerApplication.pdfViewer.pdfDocument.annotationStorage.resetModified();
    });

    function forwardEvent(name, detail)
    {
        window.parent.postMessage({emit: {name, detail}});
    }
}

function setup(dispatch, ready){
    let entries = [];
    let selecting = null; // Used to streamline select when switching editor modes.
    let updating = null; // Used to streamline updating, to prevent bogus create & delete events.
    let deletedIds = []; // Used to prevent 'delete' events that are triggered manually.
    const selected = state(null, (oldOne, newOne) => {
        selecting = null;
        const ret = (oldOne || {}).returnPending;
        ret && ret();
    });
    const uiManager = proc => pdfOnInit(x => proc(x.uiManager));
    const switchPage = switchPageWhenReady();

    uiManager(manager => {
        pdfOn('annotationeditorparamschanged', checkForChanges);
        pdfOn('switchannotationeditorparams', checkForChanges);
        pdfOnPageChanging(pageChanging);

        const actions = {
            getAll: () => entries.map(externEntry),
            setAll: newOnes => {
                entries.forEach(deleteEntry);
                entries = [];
                newOnes.forEach(actions.add);
            },
            add: newOne => {
                const id = newOne.id || uuid();
                const page = newOne.page || pdfCurrentPageIndex();
                const entry = {
                    id,
                    page,
                    text: newOne.text,
                    editor: null,
                    intern: newOne.intern
                };
                entries.push(entry);
                sync(entry, 'create', layer => {
                    return layer.deserialize(newOne.intern).then(editor => {
                        entry.editor = editor;
                        if(entry.text){
                            editor.contents = entry.text;
                        }
                        pdfAddEditorToLayerNoFocus(layer, entry.editor);
                    });
                });
            },
            'delete': id => {
                entries = entries.filter(x => {
                    if(x.id === id){
                        deleteEntry(x);
                        return false;
                    }
                    return true;
                });
            },
            update: extern => {
                const isSelected = (selected() || {}).id === extern.id;
                updating = extern.id;
                actions.delete(extern.id);
                actions.add(extern);
                if(isSelected){
                    return actions.select(extern.id);
                }
            },
            selected: () => selected() ? externEntry(selected()) : null,
            select: id => {
                const entry = entryById(id);
                selected(entry);
                const ret = sync(entry, 'select', () => {
                    if(PDFViewerApplication.pdfViewer.annotationEditorMode !== PDF_EDIT_MODE()){
                        selecting = entry.editor;
                        entry.editor.annotationElementId = entry.id;
                        actions.viewOnly(false);
                    }else{
                        manager.setSelected(entry.editor);
                    }
                });
                if(entry.page !== pdfCurrentPageIndex()){
                    switchPage(entry.page);
                }

                return ret;
            },
            currentPage: pdfCurrentPageIndex,
            viewOnly: viewOnly => {
                pdfSwitchToMode(viewOnly ? PDF_VIEW_MODE() : PDF_EDIT_MODE());
                document.querySelector('#editorHighlight').classList[viewOnly ? 'add' : 'remove']('annotate-pdf-hide');
            },
        };

        actions.viewOnly(Boolean(new URLSearchParams(window.location.search).get('viewOnly')));
        ready(actions);
        PDFViewerApplication.viewsManager.setInitialView(0);
        dispatch('ready');

        function deleteEntry(entry)
        {
            deletedIds.push(entry.id);
            entry.editor?.remove();
            const ret = entry.returnPending;
            ret && ret();
        }

        function checkForChanges(){
            const page = pdfCurrentPageIndex();
            const usedIds = Array.from(manager.getEditors(page)).map(createOrUpdateEntry.bind(null, page));
            const isUsed = x => x.page !== page || usedIds.includes(x.id) || (x.pending || []).length || updating === x.id;
            const deleted = entries.filter(x => !isUsed(x));
            entries = entries.filter(isUsed);
            updating = null;
            deleted.forEach(x => !deletedIds.includes(x.id) && dispatch('delete', externEntry(x)));
            deletedIds = deletedIds.filter(id => !deleted.find(x => x.id === id));
            updateSelection();
        }

        function pageChanging(){
            const page = pdfCurrentPageIndex();
            dispatch('pageChanged', page);
        }

        function createOrUpdateEntry(page, editor)
        {
            const newData = pdfSerializeEditor(editor);
            const s = JSON.stringify(newData);
            let entry = entryByEditor(editor);

            if(!entry){
                Promise.all(entries.filter(x => x.page === page).map(x => sync(x, 'checkCreate', Void))).then(() => {
                    if(entryByEditor(editor)){return;}
                    const id = uuid();
                    const entry = {id, page, editor, intern: pdfSerializeEditor(editor)};
                    const extern = externEntry(entry);
                    entries.push(entry);
                    dispatch('create', extern);
                    selected(entry);
                    dispatch('select', extern);
                });
                return null;
            }else if(s !== JSON.stringify(entry.intern)){
                // These are null -> NaN and rounding issues and don't need to be propagated.
                const ignore = arrayEquals(
                    ['outlines', 'rect'],
                    Object.keys((diff(newData, entry.intern) || {}).Object || {})
                );
                entry.intern = newData;
                if(!ignore){
                    dispatch('update', externEntry(entry));
                }
            }

            return entry.id;
        }

        function updateSelection()
        {
            if(selecting && manager.firstSelectedEditor === selecting){
                selecting = null;
            }else if(
                (!selecting || manager.firstSelectedEditor)
                    && manager.firstSelectedEditor !== ((selected() || {}).editor || undefined)
            ){
                const entry = manager.firstSelectedEditor ? entryByEditor(manager.firstSelectedEditor) : null;
                if(entry || selected()){
                    selected(entry);
                    dispatch('select', entry ? externEntry(entry) : null);
                }
            }
        }

        function entryByEditor(editor)
        {
            return entries.find(x => x.editor === editor);
        }

        function entryById(id)
        {
            return entries.find(x => x.id === id);
        }
    });
}

function externEntry(entry)
{
    return {
        id: entry.id,
        page: entry.page,
        intern: entry.intern,
    };
}

function uuid()
{
    return new Date().getTime() + "-" + Math.round(100000000+Math.random()*999999999);
}

function then(p, proc)
{
    return Promise.resolve(p).then(proc);
}

/**
 * Synchronize actions for entries.
 * When creating, updating, selecting annotations in the UI it is not
 * always guaranteed that the page the annotation is displayed on is
 * currently rendered, so the corresponding methods for this won't
 * work (e.g. `pdfEditLayer(page)` returns null).  This function
 * checks if the current AnnotationEditorLayer for the entry's page is
 * available and queues the action until the corresponding
 * AnnotationEditorLayer becomes available. The given name is used to
 * drop previous actions of the same name (so that e.g. 2 queued
 * update's will result in one update only).  To kill the queued
 * actions (e.g. when the entry got deleted in the meantime) call
 * entry.returnPending().  Queued actions will be run after the whole
 * chain of the previous action has been resolved.
 * This function returns a promise which resolves when the action has
 * either been completed or the action has been killed.
 *
 * @example
 * ```js
 * const dummy = {page: 3};
 * sync(dummy, 'create', layer => new Promise(ok => setTimeout(() => console.log('create end') || ok(), 1000)));
 * sync(dummy, 'update', layer => console.log('will not be displayed'));
 * sync(dummy, 'update', layer => console.log('will be displayed'));
 * ````
 * This function ensures that first "create end" will be displayed and *then* "will be displayed".
 *
 * @example:
 * ```js
 * sync(dummy, 'create', layer => new Promise(ok => setTimeout(() => console.log('create end') || ok(), 1000)));
 * sync(dummy, 'update', layer => console.log('will be displayed'));
 * dummy.returnPending();
 * ```
 * Unless `pdfEditLayer(dummy.page)` is available when this code runs nothing will be displayed.
 * If `pdfEditLayer(dummy.page)` *is* available, *only* "create end" will be displayed.
 *
 * @return {Promise}
 */
function sync(entry, name, action)
{
    const pending = (entry.pending || []).length;
    if(!pending){
        const layer = pdfEditLayer(entry.page);
        if(layer){
            const ret = enqueue();
            dequeue(layer);
            return ret;
        }

        const ret = enqueue();
        pdfOn('annotationeditorlayerrendered', layerRendered);
        entry.returnPending = () => {
            entry.pending.forEach(p => p?.return());
            entry.pending = [];
            delete entry.returnPending;
            pdfOff('annotationeditorlayerrendered', layerRendered);
        };

        return ret;
    }

    return enqueue();

    function kill(pending)
    {
        if(entry.pending.length === 1){
            pdfOff('annotationeditorlayerrendered', layerRendered);
        }
        entry.pending = entry.pending.filter(x => x !== pending);
        pending.return();
    }

    function layerRendered(event)
    {
        if(event.pageNumber - 1 === entry.page){
            const todo = entry.pending;
            const layer = pdfEditLayer(entry.page);
            dequeue(layer);
            pdfOff('annotationeditorlayerrendered', layerRendered);
        }
    }

    function enqueue()
    {
        return new Promise(function(resolve){
            const pending = {run: action, return: resolve, name};
            entry.pending = (entry.pending || []).filter(pending => {
                if(pending?.name === name){
                    kill(pending);
                    return false;
                }
                return true;
            });
            entry.pending.push(pending);
        });
    }

    function dequeue(layer)
    {
        if(entry.pending.length === 0){
            return Promise.resolve();
        }
        const head = entry.pending[0];
        entry.pending = [null].concat(entry.pending.slice(1));
        const ret = head.run(layer);
        head.return(ret);

        return then(ret, () => {
            entry.pending = entry.pending.slice(1);
            dequeue(layer);
        });
    }
}

function pdfOnInit(thunk)
{
    pdfReady(function(){
         pdfOn('annotationeditoruimanager', thunk);
    });
}

function pdfReady(proc)
{
    window.addEventListener('DOMContentLoaded', tryit);

    function tryit()
    {
        if(window.PDFViewerApplication && PDFViewerApplication.pdfViewer && PDFViewerApplication.pdfViewer.eventBus){
            proc();
        }else{
            setTimeout(tryit, 10);
        }
    }
}

/**
 * Return the AnnotationEditorLayer for the given page.
 * This functions returns null if the page has not been rendered (or
 * is off screen again).
 * Use the function sync, to run code as soon as the
 * AnnotationEditorLayer for a specific page is available.
 *
 * @return {AnnotationEditorLayer|null}
 */
function pdfEditLayer(page)
{
    return ((PDFViewerApplication.pdfViewer._pages[page] || {}).annotationEditorLayer || {}).annotationEditorLayer;
}

/**
 * Serialize an editor so that it can be deserialized again.
 * Using
 * `AnnotationEditorLayer.deserialize(HighlightEditor.serialize())`
 * directly doesn't work.
 * See also: HighlightEditor.deserialize.
 *
 * @param {HighlightEditor} editor
 * @return {Object}
 */
function pdfSerializeEditor(editor)
{
    // The annotationElementId is needed to keep an element
    // highlighted when switching modes (mode switching is
    // asynchronous with no way to wait for it). But the
    // annotationElementId breaks the serialization.
    const old = editor.annotationElementId;
    editor.annotationElementId = null;
    const obj = editor.serialize();
    editor.annotationElementId = old;

    if(obj.quadPoints){ // text drawing
        // If this is a text drawing the `quadPoints` property is
        // returned as a plain object but the
        // HighlightEditor.deserialize method expects this to be an
        // array...
        obj.quadPoints = Object.values(obj.quadPoints);
    }else{ // free hand drawing
        // This is guess work:
        // The HighlightEditor.deserialize method checks for a
        // inkLists property of the form: [0: [number, number, ...]].
        // When serializing a free hand drawing, the inkList property
        // is null but the path `outlines.points` has the same
        // structure as the expected inkList propery, so we set the
        // inkList to this path.
        // This works without any issues.
        obj.inkLists = obj.outlines.points;
    }

    return obj;
}

function pdfCurrentPageIndex()
{
    return PDFViewerApplication.pdfViewer.currentPageNumber - 1;
}

function pdfSwitchToPageIndex(page)
{
    // PDFViewerApplication.eventBus is the same object as PDFViewerApplication.pdfViewer.eventBus
    PDFViewerApplication.eventBus.dispatch('pagenumberchanged', {value: page + 1});
}

function pdfOnPageChanging(proc)
{
    PDFViewerApplication.eventBus.on('pagechanging', proc);
}

function pdfOn(n, proc)
{
    PDFViewerApplication.eventBus.on(n, proc);
}

function pdfOnce(n, proc)
{
    PDFViewerApplication.eventBus.on(n, proc, {once: true});
}

function pdfOff(n, f)
{
    PDFViewerApplication.eventBus.off(n, f);
}

function PDF_EDIT_MODE()
{
    return 9; // view / select mode = 0
}

function PDF_VIEW_MODE()
{
    return 0;
}

function pdfSwitchToMode(mode, editId = null)
{
    PDFViewerApplication.eventBus.dispatch('switchannotationeditormode', {mode, editId});
}

/**
 * This function adds a HighlightEditor to a AnnotationEditorLayer and
 * ensuring the added editor doesn't get focused. Without this
 * function it is unpredictable wether or not the editor gets selected.
 *
 * @param {AnnotationEditorLayer} layer
 * @param {HighlightEditor} editor
 */
function pdfAddEditorToLayerNoFocus(layer, editor)
{
    // Temporary overwrite prototype chain.
    editor.render = () => {
        delete editor.render;
        const ret = editor.render();
        editor.div.focus = Void; // Same again.
        return ret;
    };
    layer.add(editor);
    delete editor.div.focus;
}

function state(initValue, onUpdate = Void)
{
    return (...args) => {
        if(args.length === 0){
            return initValue;
        }
        onUpdate(initValue, args[0]);
        initValue = args[0];
    };
}

function switchPageWhenReady()
{
    let call = state(Void);
    pdfReady(() => pdfOnce('pagesloaded', () => {
        call()();
        call = proc => proc();
    }));

    return nr => {
        call(() => pdfSwitchToPageIndex(nr));
    };
}

function arrayEquals(left, right)
{
    return diff(left, right) === null;
}

function diff(left, right)
{
    if(left === right){
        return null;
    }
    const t = typeof left;
    if(t !== typeof right){
        return {leftType: t, rightType: typeof right};
    }
    if(t !== 'object'){
        return {left, right};
    }

    if(left instanceof Array){
        if(!(right instanceof Array)){
            return {leftType: 'Array', rightType: right.constructor.name || 'dunno'};
        }
        const diffs = {};
        for(let i = 0; i < Math.max(left.length, right.length); i++){
            const d = diff(left[i], right[i]);
            if(d !== null){
                diffs[i] = d;
            }
        }
        if(Object.values(diffs).length){
            return {Array: diffs};
        }
        return null;
    }

    const keys = new Set();
    Object.keys(left).concat(Object.keys(right)).forEach(keys.add.bind(keys));

    const diffs = {};
    keys.forEach(key => {
        const d = diff(left[key], right[key]);
        if(d !== null){
            diffs[key] = d;
        }
    });

    if(Object.values(diffs).length){
        return {Object: diffs};
    }

    return null;
}

function overwriteMethodOnce(obj, method, instead = Void)
{
    obj[method] = () => {
        delete obj[method];
        return instead(obj[method]);
    };
}

function Void(){}
