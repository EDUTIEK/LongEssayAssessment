import createPDFJsApi from '../../node_modules/annotate-pdf/pdfjs-api';

export default class PdfViewer
{
  init(id, url) {
    const element = document.getElementById(id);
    console.log(url);
    createPDFJsApi(element, 'components/EDUTIEK/LongEssayAssessment/annotate-pdf/pdfjs-dist/web/viewer.html', url, {viewOnly: true});
  }
}