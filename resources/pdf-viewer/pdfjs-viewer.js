run();

function run()
{
    const uiManager = proc => pdfOnInit(x => proc(x.uiManager));
    pdfReady(() => {
        PDFViewerApplication.eventBus.on('annotationeditoruimanager', e => init(e.uiManager));
    });
}

function init(uiManager)
{
    // const s = PDFViewerApplication.pdfViewer.pdfDocument.annotationStorage;
    // window.suu = s;
    // console.log('>', s);

    // PDFViewerApplication.pdfViewer._pages[0].annotationLayer.annotationLayer.edutiekElements();
    const root = document.getElementById('edutiek-annotations');
    PDFViewerApplication.pdfDocument.getPage(1).then(page => {
        page.getAnnotations().then(an => {
            console.log(an);
        });
    });

    PDFViewerApplication.eventBus.on('annotationeditorlayerrendered', event => {
        PDFViewerApplication.pdfViewer._pages[event.pageNumber - 1].annotationLayer.annotationLayer.edutiekElements().forEach(an => {
            console.log(an);
            if (!annotationEmpty(an)) {
                root.appendChild(buildAnnotationNode(an));
            }
        });
    });
}

function annotationEmpty(annotation)
{
    return !annotation.data.contentsObj.str;
}

function buildAnnotationNode(annotation)
{
    const node = document.createElement('div');
    buildAnnotationNode.c ||= [];
    buildAnnotationNode.c.push({c: annotation.container, n: node});
    node.classList.add('edutiek-annotation');
    node.textContent = annotation.data.contentsObj.str;
    const click = e => {
        buildAnnotationNode.c.forEach(({c, n}) => (c.classList.remove('focus'), n.classList.remove('focus')));
        annotation.container.classList.add('focus');
        node.classList.add('focus');
        (e.target === node ? annotation.container : node).scrollIntoView({
            block: 'center',
            behaviour: 'instant',
        });
    };
    node.addEventListener('click', click);
    annotation.container.addEventListener('click', click);

    node.addEventListener('mouseenter', e => {
        annotation.container.classList.add('hover');
    });
    node.addEventListener('mouseleave', e => {
        annotation.container.classList.remove('hover');
    });

    annotation.container.addEventListener('mouseenter', e => {
        node.classList.add('hover');
    });
    annotation.container.addEventListener('mouseleave', e => {
        node.classList.remove('hover');
    });

    return node;
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
