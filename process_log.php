<?php

class LogEntry
{
    public string $id;
    public string $userID;
    public int $bytesTX;
    public int $bytesRX;
    public DateTime $timestamp;

    public function __construct(string $id, string $userID, int $bytesTX, int $bytesRX, string $datetime)
    {
        $this->id = $id;
        $this->userID = $userID;
        $this->bytesTX = $bytesTX;
        $this->bytesRX = $bytesRX;
        $this->timestamp = new DateTime($datetime);
    }

    public function toPipeDelimited(): string
    {
        $formattedDate = $this->timestamp->format('D, F d Y, H:i:s');
        return sprintf(
            "%s|%s|%s|%s|%s",
            $this->userID,
            number_format($this->bytesTX),
            number_format($this->bytesRX),
            $formattedDate,
            $this->id
        );
    }
}

class LogParser
{
   
    public static function parse(string $filepath): array
    {
        $entries = [];

        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line);
            if (count($parts) < 5) continue;

            $id = $parts[0];
            $userID = $parts[1];
            $bytesTX = (int)$parts[2];
            $bytesRX = (int)$parts[3];
            $datetime = $parts[4] . ' ' . $parts[5];

            $entries[] = new LogEntry($id, $userID, $bytesTX, $bytesRX, $datetime);
        }

        return $entries;
    }
}

class LogWriter
{
    private array $entries;

    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    public function writeToFile(string $filename): void
    {
        $output = [];

        
        $output[] = "Section 1: Pipe-delimited logs";
        foreach ($this->entries as $entry) {
            $output[] = $entry->toPipeDelimited();
        }

        
        $output[] = PHP_EOL . "Section 2: Sorted list of IDs";
        $ids = array_map(fn($e) => $e->id, $this->entries);
        sort($ids);
        $output = array_merge($output, $ids);

        
        $output[] = PHP_EOL . "Section 3: Unique UserIDs sorted, numbered";
        $userIDs = array_unique(array_map(fn($e) => $e->userID, $this->entries));
        sort($userIDs);
        foreach ($userIDs as $index => $userID) {
            $output[] = sprintf("[%d] %s", $index + 1, $userID);
        }

        file_put_contents($filename, implode(PHP_EOL, $output));
    }
}


$logFile = 'sample-log.txt';         
$outputFile = 'output.txt';   

$entries = LogParser::parse($logFile);
$writer = new LogWriter($entries);
$writer->writeToFile($outputFile);

echo "✅ Log has been processed. Output written to '{$outputFile}'." . PHP_EOL;
