<?php

/*
 * A stand-in for ClamAV's clamscan, used by tests/Feature/VirusScannerTest.php (run with PHP: CLAMAV_PATH = php,
 * first option = this file). Same contract as clamscan: the last argument is the file; exit 0 = clean, 1 = infected,
 * 2 = error. "Infected" means the file contains the standard harmless EICAR test marker.
 */
$file = $argv[count($argv) - 1];
$content = is_file($file) ? (string) file_get_contents($file) : '';

if (str_contains($content, 'EICAR-STANDARD-ANTIVIRUS'.'-TEST-FILE')) {
    echo "{$file}: Eicar-Test-Signature FOUND\n";
    exit(1);
}
if (str_contains($content, 'SCANNER-CRASH')) {
    fwrite(STDERR, "ERROR: Can't open file\n");
    exit(2);
}
if (str_contains($content, 'SCANNER-SLOW')) {
    sleep(5);
}
echo "{$file}: OK\n";
exit(0);
