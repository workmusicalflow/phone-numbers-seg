<?php

// Define the directory to search
$srcDir = __DIR__ . '/../src';

// Function to search for a pattern in files
function searchInFiles($dir, $pattern)
{
    $results = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $results[] = [
                    'file' => $file->getPathname(),
                    'matches' => []
                ];

                foreach ($matches[0] as $match) {
                    // Get the line number
                    $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;

                    // Get the line content
                    $lines = explode("\n", $content);
                    $line = $lines[$lineNumber - 1];

                    // Get the context (5 lines before and after)
                    $contextStart = max(0, $lineNumber - 5);
                    $contextEnd = min(count($lines), $lineNumber + 5);
                    $context = [];
                    for ($i = $contextStart; $i < $contextEnd; $i++) {
                        $context[] = [
                            'line' => $i + 1,
                            'content' => $lines[$i]
                        ];
                    }

                    $results[count($results) - 1]['matches'][] = [
                        'line' => $lineNumber,
                        'content' => trim($line),
                        'context' => $context
                    ];
                }
            }
        }
    }

    return $results;
}

// Search for GraphQLFormatterService
echo "Searching for GraphQLFormatterService...\n";
$pattern = '/GraphQLFormatterService/i';
$results = searchInFiles($srcDir, $pattern);

if (empty($results)) {
    echo "No references to GraphQLFormatterService found.\n";
} else {
    echo "Found references to GraphQLFormatterService:\n";
    foreach ($results as $result) {
        echo "File: " . $result['file'] . "\n";
        foreach ($result['matches'] as $match) {
            echo "  Line " . $match['line'] . ": " . $match['content'] . "\n";
            echo "  Context:\n";
            foreach ($match['context'] as $contextLine) {
                echo "    Line " . $contextLine['line'] . ": " . $contextLine['content'] . "\n";
            }
            echo "\n";
        }
    }
}

// Search for new GraphQLFormatterService
echo "\nSearching for 'new GraphQLFormatterService'...\n";
$pattern = '/new\s+(?:\\\\?App\\\\GraphQL\\\\Formatters\\\\)?GraphQLFormatterService\s*\(/i';
$results = searchInFiles($srcDir, $pattern);

if (empty($results)) {
    echo "No instances of 'new GraphQLFormatterService' found.\n";
} else {
    echo "Found instances of 'new GraphQLFormatterService':\n";
    foreach ($results as $result) {
        echo "File: " . $result['file'] . "\n";
        foreach ($result['matches'] as $match) {
            echo "  Line " . $match['line'] . ": " . $match['content'] . "\n";
            echo "  Context:\n";
            foreach ($match['context'] as $contextLine) {
                echo "    Line " . $contextLine['line'] . ": " . $contextLine['content'] . "\n";
            }
            echo "\n";
        }
    }
}

// Search for GraphQLFormatterInterface
echo "\nSearching for GraphQLFormatterInterface...\n";
$pattern = '/GraphQLFormatterInterface/i';
$results = searchInFiles($srcDir, $pattern);

if (empty($results)) {
    echo "No references to GraphQLFormatterInterface found.\n";
} else {
    echo "Found references to GraphQLFormatterInterface:\n";
    foreach ($results as $result) {
        echo "File: " . $result['file'] . "\n";
        foreach ($result['matches'] as $match) {
            echo "  Line " . $match['line'] . ": " . $match['content'] . "\n";
            echo "  Context:\n";
            foreach ($match['context'] as $contextLine) {
                echo "    Line " . $contextLine['line'] . ": " . $contextLine['content'] . "\n";
            }
            echo "\n";
        }
    }
}

// Search for GraphQLFormatter
echo "\nSearching for GraphQLFormatter...\n";
$pattern = '/GraphQLFormatter/i';
$results = searchInFiles($srcDir, $pattern);

if (empty($results)) {
    echo "No references to GraphQLFormatter found.\n";
} else {
    echo "Found references to GraphQLFormatter:\n";
    foreach ($results as $result) {
        echo "File: " . $result['file'] . "\n";
        foreach ($result['matches'] as $match) {
            echo "  Line " . $match['line'] . ": " . $match['content'] . "\n";
            echo "  Context:\n";
            foreach ($match['context'] as $contextLine) {
                echo "    Line " . $contextLine['line'] . ": " . $contextLine['content'] . "\n";
            }
            echo "\n";
        }
    }
}
