<?php

namespace PropertySuggester\UpdateTable\Importer;

use DatabaseBase;
use PropertySuggester\UpdateTable\ImportContext;
use InvalidArgumentException;

/**
 * A strategy, which imports entries from CSV file into DB table, used as fallback, when no special import commands
 * are supported by the dbms.
 *
 * @author BP2013N2
 * @licence GNU GPL v2+
 */
class BasicImporter implements Importer {

	/**
	 * Import using SQL Insert
	 * @param ImportContext $importContext
	 * @return bool
	 */
	function importFromCsvFileToDb( ImportContext $importContext ) {

		if ( ( $fileHandle = fopen( $importContext->getCsvFilePath(), "r" ) ) == false ) {
			return false;
		}

		$lb = $importContext->getLb();
		$db = $lb->getConnection( DB_MASTER );
		$this->doImport( $fileHandle, $db, $importContext );
		$lb->reuseConnection( $db );

		fclose( $fileHandle );

		return true;
	}

	/**
	 * @param $fileHandle
	 * @param DatabaseBase $db
	 * @param ImportContext $importContext
	 */
	private function doImport( $fileHandle, DatabaseBase $db, ImportContext $importContext ) {
		$accumulator = array();
		$i = 0;
		$header = fgetcsv( $fileHandle, 0, $importContext->getCsvDelimiter() ); //this is to get the csv-header
		if( $header != array( 'pid1', 'qid1', 'pid2', 'count', 'probability', 'context' ) ) {
			throw new InvalidArgumentException( "provided csv-file does not match the expected format:\n'pid1', 'qid1', 'pid2', 'count', 'probability', 'context'" );
		}
		while ( true ) {
			$data = fgetcsv( $fileHandle, 0, $importContext->getCsvDelimiter() );

			if ( $data == false || ++$i > 1000 ) {
				$db->insert( $importContext->getTargetTableName(), $accumulator );
				if ( $data ) {
					$accumulator = array();
					$i = 0;
				} else {
					break;
				}
			}

			$qid1 = is_numeric($data[1]) ? $data[1] : null;

			$accumulator[] = array( 'pid1' => $data[0], 'qid1' => $qid1, 'pid2' => $data[2], 'count' => $data[3],
									'probability' => $data[4], 'context' => $data[5] );
		}
	}

}
