<?php
$directory = new RecursiveDirectoryIterator('resources/views/academy');
$iterator = new RecursiveIteratorIterator($directory);
foreach ($iterator as $file) {
    if ($file->isFile() && strpos($file->getPathname(), '.blade.php') !== false) {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        // Classes
        $replacements = [
            '/(?<![a-zA-Z0-9-])right-(?!0-9a-zA-Z-)/' => 'end-',
            '/(?<![a-zA-Z0-9-])left-(?!0-9a-zA-Z-)/' => 'start-',
            '/(?<![a-zA-Z0-9-])pl-(?!0-9a-zA-Z-)/' => 'ps-',
            '/(?<![a-zA-Z0-9-])pr-(?!0-9a-zA-Z-)/' => 'pe-',
            '/(?<![a-zA-Z0-9-])ml-(?!0-9a-zA-Z-)/' => 'ms-',
            '/(?<![a-zA-Z0-9-])mr-(?!0-9a-zA-Z-)/' => 'me-',
            '/(?<![a-zA-Z0-9-])border-l(?!-)/' => 'border-s',
            '/(?<![a-zA-Z0-9-])border-r(?!-)/' => 'border-e',
            '/(?<![a-zA-Z0-9-])rounded-l-/' => 'rounded-s-',
            '/(?<![a-zA-Z0-9-])rounded-r-/' => 'rounded-e-',
            '/(?<![a-zA-Z0-9-])rounded-tl-/' => 'rounded-ss-',
            '/(?<![a-zA-Z0-9-])rounded-tr-/' => 'rounded-se-',
            '/(?<![a-zA-Z0-9-])rounded-bl-/' => 'rounded-es-',
            '/(?<![a-zA-Z0-9-])rounded-br-/' => 'rounded-ee-',
            '/(?<![a-zA-Z0-9-])text-left(?!-)/' => 'text-start',
            '/(?<![a-zA-Z0-9-])text-right(?!-)/' => 'text-end',
            '/&larr;/' => '<span class="inline-block rtl:rotate-180">&larr;</span>',
            '/&rarr;/' => '<span class="inline-block rtl:rotate-180">&rarr;</span>',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated " . $file->getPathname() . "\n";
        }
    }
}
echo "Done\n";
