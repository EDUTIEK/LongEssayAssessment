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
export default (parent, viewer, pdf, options = {}) => {
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
}
