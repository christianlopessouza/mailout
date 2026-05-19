<?php

function mailoutVersionedSecretFiles(): array
{
    $root = dirname(__DIR__, 3);

    $files = [
        $root . DIRECTORY_SEPARATOR . '.env.example',
        $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php',
    ];

    $commandsPath = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands';
    $commandFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($commandsPath));

    foreach ($commandFiles as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getRealPath();
        }
    }

    return $files;
}

it('keeps versioned configuration and migration commands free of real credentials', function () {
    $findings = [];

    $patterns = [
        '/AKIA[0-9A-Z]{16}/' => 'AWS access key literal',
        '/[A-Za-z0-9.-]+\.rds\.amazonaws\.com/' => 'public RDS host literal',
        '/env\(\s*[\'"][A-Z0-9_]*(?:PASSWORD|SECRET|TOKEN|KEY)[A-Z0-9_]*[\'"]\s*,\s*[\'"][^\'"]{8,}[\'"]\s*\)/' => 'secret fallback in env()',
        '/Crypto::encrypt\(\s*[\'"][^\'"]{16,}[\'"]\s*\)/' => 'encrypted hardcoded secret literal',
    ];

    foreach (mailoutVersionedSecretFiles() as $file) {
        $contents = file_get_contents($file);
        $relativePath = str_replace(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR, '', $file);

        foreach ($patterns as $pattern => $description) {
            if (preg_match($pattern, $contents)) {
                $findings[] = "{$relativePath}: {$description}";
            }
        }
    }

    expect($findings)->toBeEmpty();
});

it('keeps .env.example values as safe placeholders', function () {
    $root = dirname(__DIR__, 3);
    $envExample = file($root . DIRECTORY_SEPARATOR . '.env.example', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $findings = [];

    foreach ($envExample as $line) {
        if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B'\"");

        if (! preg_match('/(?:PASSWORD|SECRET|TOKEN|KEY)$/', $key)) {
            continue;
        }

        if ($value !== '' && ! in_array(strtolower($value), ['null', 'changeme', 'placeholder', 'example'], true)) {
            $findings[] = "{$key} should be empty or a safe placeholder";
        }
    }

    expect($findings)->toBeEmpty();
});
