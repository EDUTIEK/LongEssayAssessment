import createPDFJsApi from '../../node_modules/annotate-pdf/pdfjs-api';

export default class PdfViewer
{
  init(id, viewer, url) {
    const element = document.getElementById(id);
    createPDFJsApi(element, viewer, url, {viewOnly: true});
  }
}