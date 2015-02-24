<?php

require_once("./Services/PDFGeneration/classes/class.ilPDFGeneration.php");
require_once("./Services/PDFGeneration/classes/class.ilPDFGenerationJob.php");
require_once 'class.ilRoomSharingTCPDFGenerator.php';

/**
 * Inherits from ilPDFGeneration
 * Overrides the doJob Method to call the custom ilRoomSharingTCPDFGenerator
 * in order to create PDFs in landscape mode
 *
 * @author MartinDoser
 */
class ilRoomSharingPDFGeneration extends ilPDFGeneration
{
	/**
	 * Method executes ilPDFGenerationJob
	 * calls custom method ilRoomSharingTCPDFGenerator to generate PDFs in landscape orientation
	 *
	 * @param ilPDFGenerationJob $job
	 */
	public static function doJob(ilPDFGenerationJob $job)
	{
		/*
		 * This place currently supports online the TCPDF-Generator. In future versions/iterations, this place
		 * may serve to initialize other mechanisms and route jobs to them.
		 */
		ilRoomSharingTCPDFGenerator::generatePDF($job);
	}

}
