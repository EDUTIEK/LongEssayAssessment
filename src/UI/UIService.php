<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI;

use ILIAS\Refinery\Factory;

class UIService
{
    private \ilLanguage $lng;
    private Factory $refinery;
    private \ilPlugin $plugin;

    public function __construct(\ilPlugin $plugin, \ilLanguage $lng, Factory $refinery)
    {
        $this->lng = $lng;
        $this->refinery = $refinery;
        $this->plugin = $plugin;
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


    public function checkAllInMultiselectFilter(): \Closure
    {
        $check_all = $this->plugin->txt("check_all");
        $all_checked = $this->plugin->txt("all_checked");

        return function ($id) use ($check_all, $all_checked) {
            return "
                    $('#{$id}').prepend('<li><input id=\"{$id}_allcheck\" type=\"checkbox\"> <span class=\"hidden\">{$all_checked}</span>{$check_all}</li>');
                    
                    var {$id}_check = function myFunction() {
                        if($('#{$id}').find('input[type=\"checkbox\"][name^=\"filter_input_\"]').length === $('#{$id}').find('input[type=\"checkbox\"][name^=\"filter_input_\"]:checked').length )
                        {
                            $('#{$id}_allcheck').prop('checked', true);
                        } else {
                            $('#{$id}_allcheck').prop('checked', false);
                        }
                    } 
                    {$id}_check();
                    
                    $('#{$id}_allcheck').change(function() {
                        if(this.checked) {
                            $('#{$id}').find('input').prop('checked', true);
                        }else{
                            $('#{$id}').find('input').prop('checked', false);
                        }
                    });
                    $('#{$id}').find('input[name^=\"filter_input_\"]').change(function() {
                        setTimeout({$id}_check, 100);
                    });
                 ";
        };
    }
}
