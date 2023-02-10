<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI;

class UIService
{
	private \ilLanguage $lng;

	public function __construct(\ilLanguage $lng)
	{
		$this->lng = $lng;
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
}