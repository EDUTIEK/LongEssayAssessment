import il from 'ilias';
import Fixation from "./src/Js/Fixation.js";
import PdfViewer from "./src/Js/PdfViewer.js"
import TinyHelper from "./src/Js/TinyHelper.js";
import ToolsHandler from "./src/Js/ToolsHandler.js";

il.Xlas = il.Xlas || {};
il.Xlas.Fixation = il.Xlas.Fixation || new Fixation();
il.Xlas.PdfViewer = il.Xlas.PdfViewer || new PdfViewer();
il.Xlas.TinyHelper = il.Xlas.TinyHelper || new TinyHelper();
il.Xlas.ToolsHandler = il.Xlas.ToolsHandler || new ToolsHandler();

