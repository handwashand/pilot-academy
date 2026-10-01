<?php
$files = glob(__DIR__ . '/lang/ar/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $modified = false;
    foreach ($lines as $i => &$line) {
        if (strpos($line, '=>') !== false && strpos($line, '|') !== false) {
            // Ignore correction_help
            if (strpos($line, 'correction_help') !== false) continue;
            
            preg_match('/\'([^\']+)\'/', explode('=>', $line)[1] ?? '', $matches);
            if (isset($matches[1]) && substr_count($matches[1], '|') === 1) {
                $parts = explode('|', $matches[1]);
                $zero = $parts[0];
                $one = $parts[0];
                $two = $parts[1];
                $few = $parts[1];
                $many = $parts[1];
                $other = $parts[1];
                $newStr = "$zero|$one|$two|$few|$many|$other";
                $line = str_replace($matches[1], $newStr, $line);
                $modified = true;
            }
        }
    }
    if ($modified) {
        file_put_contents($file, implode("\n", $lines));
        echo "Updated $file\n";
    }
}
echo "Done\n";
