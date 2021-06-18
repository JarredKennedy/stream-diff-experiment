# Stream Diff Experiment

## Goal
Re-implement the Longest Common Subsequence reference implementation from the [LCS Wikipedia Page](https://en.wikipedia.org/wiki/Longest_common_subsequence_problem#Computing_the_length_of_the_LCS) with an input of two streams of newline delimited elements without using seeking in an interation.  

The same implementation, but the sequence lengths are not known ahead of time. The naive solution is to read a line from stream A, then read all lines from stream B, calling `rewind` on stream B for each line in stream A. The problem is that stream B could be a large file and seeking back to the beginning of that file could mean it needs to be read from disk for every line in stream A, which is likely many lines when stream B has many lines.

## Usage
```
vendor/bin/phpunit
```