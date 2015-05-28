<?php

require_once('./Services/PDFGeneration/classes/class.ilTCPDFGenerator.php');
require_once('./Services/PDFGeneration/classes/tcpdf/tcpdf.php');

/**
 * Inherits from ilTCPDFGeneration.
 * Overrides generatePDF method to generate PDF in landscape mode instead of portrait mode.
 *
 * @author MartinDoser
 */
class ilRoomSharingTCPDFGenerator extends ilTCPDFGenerator {

	/**
	 * Method creates a PDF in landscape mode, using ilPDFGeneration Job
	 * Rest of method similar to superclass method
	 *
	 * @param ilPDFGenerationJob $job
	 */
	public static function generatePDF(ilPDFGenerationJob $job) {
		// create new PDF document
		// 'L' for Landscape
		$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator($job->getCreator());
		$pdf->SetAuthor($job->getAuthor());
		$pdf->SetTitle($job->getTitle());
		$pdf->SetSubject($job->getSubject());
		$pdf->SetKeywords($job->getKeywords());

		$pdf->setHeaderFont(Array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ));
		$pdf->setFooterFont(Array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ));
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins($job->getMarginLeft(), $job->getMarginTop(), $job->getMarginRight());
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak($job->getAutoPageBreak(), $job->getMarginBottom());
		$pdf->setImageScale($job->getImageScale());
		$pdf->SetFont('dejavusans', '', 10);

		foreach ($job->getPages() as $page) {
			$pdf->AddPage();
			$pdf->writeHTML($page, true, false, true, false, '');
		}

		$result = $pdf->Output($job->getFilename(), $job->getOutputMode()); // (I - Inline, D - Download, F - File)
	}
}
