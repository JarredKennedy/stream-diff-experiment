<?php

class DiffOp {

	public array $originalLines = [];

	public array $patchedLines = [];

	public int $originalLineStart = 0;

	public int $patchedLineStart = 0;

	public int $originalLinesEffected = 0;

	public int $patchedLinesEffected = 0;

	public function equals( DiffOp $op ) : bool {
		return (
			$this->originalLines == $op->originalLines
			&& $this->patchedLines == $op->patchedLines
			&& $this->originalLineStart === $op->originalLineStart
			&& $this->patchedLineStart === $op->patchedLineStart
			&& $this->originalLinesEffected === $op->originalLinesEffected
			&& $this->patchedLinesEffected === $op->patchedLinesEffected
		);
	}

}