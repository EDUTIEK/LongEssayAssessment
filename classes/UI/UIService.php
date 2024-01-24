<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI;

use ILIAS\Refinery\Factory;

class UIService
{
    private \ilLanguage $lng;
    private Factory $refinery;

    private array $rte_tag_set = array(
        "mini" => array("strong", "em", "u", "ol", "li", "ul", "blockquote", "a", "p", "span", "br"), // #13286/#17981
        "standard" => array("strong", "em", "u", "ol", "li", "ul", "p", "div",
            "i", "b", "code", "sup", "sub", "pre", "strike", "gap"),
        "extended" => array(
            "a","blockquote","br","cite","code","div","em","h1","h2","h3",
            "h4","h5","h6","hr","li","ol","p",
            "pre","span","strike","strong","sub","sup","u","ul",
            "i", "b", "gap"),
        "extended_table" => array(
            "a","blockquote","br","cite","code","div","em","h1","h2","h3",
            "h4","h5","h6","hr","li","ol","p",
            "pre","span","strike","strong","sub","sup","table","td",
            "tr","u","ul", "i", "b", "gap"),
        "full" => array(
            "a","blockquote","br","cite","code","div","em","h1","h2","h3",
            "h4","h5","h6","hr","li","ol","p",
            "pre","span","strike","strong","sub","sup","table","td",
            "tr","u","ul","ruby","rbc","rtc","rb","rt","rp", "i", "b", "gap"));


    public function __construct(\ilLanguage $lng, Factory $refinery)
    {
        $this->lng = $lng;
        $this->refinery = $refinery;
    }

    public function getMaxFileSizeString()
    {
        // get the value for the maximal uploadable filesize from the php.ini (if available)
        $umf = ini_get("upload_max_filesize");
        // get the value for the maximal post data from the php.ini (if available)
        $pms = ini_get("post_max_size");

        //convert from short-string representation to "real" bytes
        $multiplier_a = array("K" => 1024, "M" => 1024 * 1024, "G" => 1024 * 1024 * 1024);

        $umf_parts = preg_split("/(\d+)([K|G|M])/", $umf, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $pms_parts = preg_split("/(\d+)([K|G|M])/", $pms, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if (count($umf_parts) == 2) {
            $umf = $umf_parts[0] * $multiplier_a[$umf_parts[1]];
        }
        if (count($pms_parts) == 2) {
            $pms = $pms_parts[0] * $multiplier_a[$pms_parts[1]];
        }

        // use the smaller one as limit
        $max_filesize = min($umf, $pms);

        if (!$max_filesize) {
            $max_filesize = max($umf, $pms);
        }

        //format for display in mega-bytes
        $max_filesize = sprintf("%.1f MB", $max_filesize / 1024 / 1024);

        return $this->lng->txt("file_notice") . " " . $max_filesize;
    }

    /**
     * By calling this method all textareas are converted to ILIASs custom tinyMCE
     * Caution:
     * - Only works with the modified version of textarea from the local ui factory, due to unremovable striptags transformation
     * - Is not working when called in an asyncrounus environment but has to be called in the parent frame
     * - use noRTEOnloadCode to exclude a specific textarea which has to be added to js onload code BEFORE this is called
     *
     * @param string $mode (mini|standard|extended|extended_table|full)
     * @param int $width
     * @return void
     */
    public function addTinyMCEToTextareas(string $mode = "standard", int $width = 795): void
    {
        if(!array_key_exists($mode, $this->rte_tag_set)) {
            return;
        }

        $rte = new \ilTinyMCE();
        $rte->setInitialWidth($width);
        $rte->addPlugin("emoticons");

        if($mode === "mini") {
            $rte->removeAllPlugins();
            $rte->addPlugin("paste");
            $rte->addPlugin("lists");
            $rte->addPlugin("link");
            $rte->addPlugin("code");
            if (method_exists($rte, 'removeAllContextMenuItems')) {
                $rte->removeAllContextMenuItems();
            }
            $rte->disableButtons(array("anchor", "alignleft", "aligncenter",
                "alignright", "alignjustify", "formatselect", "removeformat",
                "cut", "copy", "paste", "pastetext")); // JF, 2013-12-09
        }

        $rte->addCustomRTESupport(0, "", $this->rte_tag_set[$mode]);
    }

    /**
     * a transformation to remove tags which are not allowed in this rte environment
     *
     * @param string $mode
     * @return \ILIAS\Refinery\Custom\Transformation|\ILIAS\Refinery\Custom\Transformations\Transformation
     */
    public function stringTransformationByRTETagSet(string $mode = "standard")
    {
        $allowed_tags = $this->rte_tag_set[$mode] ?? [];
        $allowed_tags[] = "span";//some styles are made with spans not with tags
        return $this->refinery->custom()->transformation(function ($x) use ($allowed_tags) {
            return strip_tags($x, $allowed_tags);
        });
    }

    /**
     * if a plain textarea is used next to a RTE textares this closure has to be added toe its onload code.
     * attention: this onloadcode needs to be called before RTE initialisiation!
     *
     * @return \Closure
     */
    public function noRTEOnloadCode()
    {
        return function ($id) {
            return "$('#{$id}').addClass('noRTEditor');";
        };
    }
}
