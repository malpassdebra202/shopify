<?php
header('Content-Type: text/plain');

$sessionsFile = 'sessions.json';

echo "Current sessions file contents:\n";
if (file_exists($sessionsFile)) {
    echo file_get_contents($sessionsFile);
} else {
    echo "Sessions file does not exist";
}

echo "\n\nFile permissions:\n";
echo substr(sprintf('%o', fileperms($sessionsFile)), -4);

echo "\n\nWritable test:\n";
if (is_writable($sessionsFile)) {
    echo "File is writable";
} else {
    echo "File is NOT writable";
} 