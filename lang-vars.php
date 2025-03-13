#!/usr/bin/env php
<?php declare(strict_types=1);

if (php_sapi_name() === 'cli') {
    main();
}

function main(): void
{
    if (in_array(arg(1, ''), ['', 'help'], true) || flag('--help') | flag('-h')) {
        help(STDOUT);
        return;
    }

    $in = option('-in');
    $out = option('-out');

    $ins = [
        'php' => fn($f) => require $f,
        'lang' => 'read_lang_files',
        'json' => fn($f) => with_read_file($f, fn($f) => json_decode(stream_get_contents($f), true)),
    ];

    $outs = [
        'php' => fn($f, $data) => with_write_file($f, fn($f) => export($f, $data)),
        'lang' => 'write_lang_files',
        'json' => fn($f, $data) => with_write_file($f, fn($f) => fwrite($f, json_encode($data))),
    ];

    $outs[$out](arg(2, '-'), $ins[$in](arg(1, '-')));
}

function read_lang_files(string $file): array
{
    if (!is_dir($file)) {
        error('Given lang file argument is not a folder');
    }

    $out = [];
    $file = rtrim($file, '/');
    foreach (scandir($file) as $name) {
        if (preg_match('/^ilias_([[:alpha:]]+)\\.lang$/', $name, $match)) {
            $out[$match[1]] = with_read_file($file . '/' . $name, 'read_lang');
        }
    }
    return $out;
}

function write_lang_files(string $file, array $data): void
{
    if (!is_dir($file)) {
        error('Given lang file argument is not a folder');
    }

    $file = rtrim($file, '/');
    foreach ($data as $lang => $info) {
        write_lang_keep_header($file . '/ilias_' . $lang . '.lang', $info);
    }
}

function write_lang_keep_header(string $file, array $data): void
{
    $header = is_readable($file) ? with_read_file($file, 'read_lang_header') : '';
    with_write_file($file, function ($f) use ($header, $data): void {
        fwrite($f, $header);
        write_lang($f, $data);
    });
}

function write_lang($file, array $data): void
{
    ksort($data);
    foreach ($data as $id => $txt) {
        fprintf($file, '%s#:#%s' . PHP_EOL, $id, $txt);
    }
}

function read_lang($file): array
{
    $out = [];
    while (($line = fgets($file))) {
        $line = rtrim($line, "\n\r");
        if (preg_match('/^[[:alnum:]]/', $line)) {
            $pos = strpos($line, '#:#');
            $out[substr($line, 0, $pos)] = substr($line, $pos + 3);
        }
    }

    return $out;
}

function read_lang_header($file): string
{
    $header = [];
    while (($line = fgets($file))) {
        if (preg_match('/^[[:alnum:]]/', $line)) {
            break;
        }
        $header[] = $line;
    }

    return join('', $header);
}

function help($file): void
{
    fprintf($file, "Usage:\n%s -in php|lang|json -out php|lang|json [SRC-FILE|-] [DEST-FILE|-]\n", arg(0));
}

function export($file, array $data): void
{
    fwrite($file, '<?php return ' . var_export($data, true) . ';' . PHP_EOL);
}

function with_read_file(string $filename, callable $proc)
{
    $file = $filename === '-' ? STDIN : fopen($filename, 'rb');
    if(!$file){
        error('Cannot open file "' . $filename . '" for reading.');
    }

    $ret = $proc($file);

    if ($file !== STDIN) {
        fclose($file);
    }

    return $ret;
}

function with_write_file(string $filename, callable $proc)
{
    $file = $filename === '-' ? STDOUT : fopen($filename, 'wb');
    if(!$file){
        error('Cannot open file "' . $filename . '" for writing.');
    }

    $ret = $proc($file);

    if ($file !== STDOUT) {
        fclose($file);
    }

    return $ret;
}

function error(string $message): void
{
    fprintf(STDERR, 'Error: %s' . PHP_EOL, $message);
    help(STDERR);
    exit(1);
}

function arg(int $nr, ?string $default = null): string
{
    $args = [];
    for ($i = 0; $i < count($_SERVER['argv']); $i++) {
        if (preg_match('/^-./', $_SERVER['argv'][$i])) {
            $i++;
        } else {
            $args[] = $_SERVER['argv'][$i];
        }
    }

    return $args[$nr] ?? $default ?? error('Missing argument');
}

function flag(string $flag): bool
{
    return in_array($flag, $_SERVER['argv'], true);
}

function option(string $option, ?string $default = null)
{
    $prev = null;
    foreach ($_SERVER['argv'] as $current) {
        if ($prev === $option) {
            return $current;
        }
        $prev = $current;
    }

    return $default ?? error('Missing option: ' . $option);
}
