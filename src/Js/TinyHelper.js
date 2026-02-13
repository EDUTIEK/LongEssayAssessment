import contentCss from '../../resources/css/content.css';
import tinyTexts from './TinyTexts.js'
import tinyDE from './TinyDE.js'

/**
 * Get a named text
 * @param {string }key
 * @returns {string}
 */
function t(key) {
    return tinyTexts[key] ?? key;
}

export default class TinyHelper
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
            valid_elements: this.tinyValidElements(formatting_options),
            valid_styles: this.tinyValidStyles(formatting_options),
            formats: this.tinyFormats(),
            style_formats: this.tinyStyleFormats(formatting_options, headline_scheme),
            custom_undo_redo_levels: 10,
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
            table_toolbar: 'tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
            pagebreak_separator: '<hr>',
            pagebreak_split_block: true,  // ensures clean split, important for xsl in backend
        })
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
                    '*': 'background-color,color,text-align,mce-pagebreak,padding-left'
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
            case'minimal':
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
}