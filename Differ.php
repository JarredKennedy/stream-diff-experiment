<?php

class Differ {

	public function diff($original, $modified) : array {
		$ops = [];

		$originalSequence = [];
		$modifiedSequence = [];

		// Read and tokenize lines to conserve memory
		while (($line = fgets($original))) {
			$originalSequence[] = $this->tokenize($line);
		}

		// Do the same for the modified lines
		while (($line = fgets($modified))) {
			$modifiedSequence[] = $this->tokenize($line);
		}

		$matrix = [];

		for ($i = 0; $i <= count($originalSequence); $i++) {
			$matrix[$i] = array_fill(0, count($modifiedSequence) + 1, 0);
		}

		for ($i = 1; $i <= count($originalSequence); $i++) {
			for ($j = 1; $j <= count($modifiedSequence); $j++) {
				if ($originalSequence[$i - 1] == $modifiedSequence[$j - 1]) {
					$matrix[$i][$j] = $matrix[$i - 1][$j - 1] + 1;
				} else {
					$matrix[$i][$j] = max($matrix[$i][$j - 1], $matrix[$i - 1][$j]);
				}
			}
		}

		list($originalLines, $modifiedLines) = $this->getEffectedLines($matrix, $originalSequence, $modifiedSequence, count($originalSequence) - 1, count($modifiedSequence) - 1);

		if (count($originalLines)) {
			rewind($original);
		}

		if (count($modifiedLines)) {
			rewind($modified);	
		}

		$op = new DiffOp;
		$originalLine = '';
		$modifiedLine = '';
		$originalLineNumbersRead = 0;
		$modifiedLineNumbersRead = 0;
		$originalLineNumber = array_shift($originalLines);
		$modifiedLineNumber = array_shift($modifiedLines);
		$lastOriginalLineNumber = [0, 0];
		$lastModifiedLineNumber = [0, 0];
		$readOriginal = false;
		$readModified = false;
		$linesAdded = 0;
		$linesRemoved = 0;
		while ($originalLineNumber || $modifiedLineNumber) {
			if ($originalLineNumber && $modifiedLineNumber && $originalLineNumber[0] == $modifiedLineNumber[1]) {
				$readOriginal = true;
				$readModified = true;
			} else if (!$modifiedLineNumber || ($originalLineNumber && $originalLineNumber[0] < $modifiedLineNumber[1])) {
				$readOriginal = true;
			} else {
				$readModified = true;
			}

			if ($readOriginal) {
				if ($op->originalLineStart > 0 && $lastOriginalLineNumber[0] > 0 && $originalLineNumber[0] > $lastOriginalLineNumber[0] + 1) {
					if ($op->patchedLineStart < 0) {
						$op->patchedLineStart = $originalLineNumber[1];
					}

					$ops[] = $op;
					$op = new DiffOp;
				}

				while ($originalLine !== false && $originalLineNumbersRead < $originalLineNumber[0]) {
					$originalLine = fgets($original);
					$originalLineNumbersRead++;
				}

				if ($op->originalLineStart < 1) {
					$op->originalLineStart = $originalLineNumber[0];
				}

				$op->originalLines[] = $originalLine;
				$op->originalLinesEffected++;

				$lastOriginalLineNumber = $originalLineNumber;
				$originalLineNumber = array_shift($originalLines);
				$linesRemoved++;
			}

			if ($readModified) {
				if ($op->patchedLineStart > 0 && $lastModifiedLineNumber[1] > 0 && $modifiedLineNumber[1] > $lastModifiedLineNumber[1] + 1) {
					if ($op->originalLineStart < 0) {
						$op->originalLineStart = $modifiedLineNumber[0];
					}

					$ops[] = $op;
					$op = new DiffOp;
				}

				while ($modifiedLine !== false && $modifiedLineNumbersRead < $modifiedLineNumber[1]) {
					$modifiedLine = fgets($modified);
					$modifiedLineNumbersRead++;
				}

				if ($op->patchedLineStart < 1) {
					$op->patchedLineStart = $modifiedLineNumber[1];
				}

				$op->patchedLines[] = $modifiedLine;
				$op->patchedLinesEffected++;

				$lastModifiedLineNumber = $modifiedLineNumber;
				$modifiedLineNumber = array_shift($modifiedLines);
				$linesAdded++;
			}

			$readOriginal = false;
			$readModified = false;
		}

		if ($op->originalLineStart > 0 || $op->patchedLineStart > 0) {
			if ($op->originalLineStart < 1) {
				$op->originalLineStart = $lastModifiedLineNumber[0];
			} else if ($op->patchedLineStart < 1) {
				$op->patchedLineStart = $lastOriginalLineNumber[1];
			}

			$ops[] = $op;
		}

		return $ops;
	}

	protected function tokenize( string $line ) : string {
		return sprintf( '%u', crc32( $line ) );
	}

	protected function getEffectedLines($matrix, $originalSequence, $modifiedSequence, $i, $j) : array {
		$originalLines = [];
		$modifiedLines = [];

		while ($i > 0 || $j > 0) {
			if ($i >= 0 && $j >= 0 && $originalSequence[$i] == $modifiedSequence[$j]) {
				$i--;
				$j--;
			} else if ($j > 0 && ($i == 0 || $matrix[$i][$j - 1] >= $matrix[$i - 1][$j])) {
				$modifiedLines[] = [$i + 1, $j + 1];
				$j--;
			} else if ($i > 0 && ($j == 0 || $matrix[$i][$j - 1] < $matrix[$i - 1][$j])) {
				$originalLines[] = [$i + 1, $j+1];
				$i--;
			}
		}

		return [
			array_reverse($originalLines),
			array_reverse($modifiedLines)
		];
	}

}