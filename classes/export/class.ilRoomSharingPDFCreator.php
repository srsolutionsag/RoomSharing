<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/export/class.ilRoomSharingPDFGeneration.php");
require_once("Services/Style/classes/class.ilStyleDefinition.php");

/**
 * Klasse zur Erstellung von PDF-Dateien aus HTML.
 *
 * @author MartinDoser
 */
class ilRoomSharingPDFCreator
{
	/**
	 * Methode zur Erstellung von PDFs
	 * output modes:
	 * PDF_OUTPUT_DOWNLOAD = 'D'
	 * PDF_OUTPUT_INLINE = 'I'
	 * PDF_OUTPUT_FILE = 'F'
	 *
	 * @param type $html_input
	 * @param type $output_mode
	 * @param string $filename
	 */
	public static function generatePDF($html_input, $output_mode, $filename)
	{
		$preprocessed_html = self::preprocessHTML($html_input);

		if (substr($filename, (strlen($filename) - 4), 4) != '.pdf')
		{
			$filename .= '.pdf';
		}

		$job = new ilPDFGenerationJob();
		$job->setAutoPageBreak(true)
			->setCreator('RoomSharing Export Test')
			->setFilename($filename)
			->setMarginLeft('20')
			->setMarginRight('20')
			->setMarginTop('20')
			->setMarginBottom('20')
			->setOutputMode($output_mode)
			->addPage($preprocessed_html);

		ilRoomSharingPDFGeneration::doJob($job);
	}

	/**
	 * Arbeitet css-Files ein html ein.
	 *
	 * @param string $html
	 * @return string
	 */
	public static function preprocessHTML($html)
	{
		$pdf_css_path = self::getTemplatePath('appointments_pdf.css');
		return '<style>' . file_get_contents($pdf_css_path) . '</style>' . $html;
	}

	/**
	 * Returns the path of the css-File.
	 *
	 * @param string $a_filename
	 * @return string
	 */
	protected static function getTemplatePath($a_filename)
	{
		$module_path = "Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/";

		if (ilStyleDefinition::getCurrentSkin() != "default")
		{
			$fname = "./Customizing/global/skin/" .
				ilStyleDefinition::getCurrentSkin() . "/" . $module_path . basename($a_filename);
		}
		if ($fname == "" || !file_exists($fname))
		{
			$fname = "./" . $module_path . "templates/default/" . basename($a_filename);
		}

		return $fname;
	}

}
