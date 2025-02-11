<?php

namespace Castor\Tests\Helper;

final class OutputCleaner
{
    public static function cleanOutput(string $string): string
    {
        $string = str_replace("\r\n", "\n", $string);

        // In the bash completion script ×2
        $string = str_replace('_sf_castor.linux-amd64.phar', '_sf_castor', $string);
        $string = str_replace('castor.linux-amd64.phar', 'castor', $string);
        $string = str_replace('_sf_castor.linux-amd64', '_sf_castor', $string);
        $string = str_replace('castor.linux-amd64', 'castor', $string);

        // SSH tests behave differently between local and CI
        $string = str_replace("scp: Connection closed\n", '', $string);
        $string = str_replace("lost connection\n", '', $string);

        // Clean line numbers
        $string = preg_replace('{In ([A-Z]\w+).php line \d+:}m', 'In \1.php line XXXX:', $string);
        $string = preg_replace('{In functions.php line \d+:}m', 'In functions.php line XXXX:', $string);

        // Clean the time
        $string = preg_replace('{^\d\d:\d\d:\d\d }m', 'hh:mm:ss ', $string);

        // Clean the warning on tasks when remote imports are disabled
        $string = preg_replace('{hh:mm:ss WARNING   \[castor\] Could not import "[\w:/\.-]*" in "[\w:/\.-]*" on line \d+. Reason: Remote imports are disabled\.}m', '', $string);

        // Avoid spacing issues
        $string = ltrim($string, "\n"); // Trim output start to avoid empty lines (like after removing remote import warnings)
        $string = preg_replace('/ +$/m', '', $string); // Remove trailing space

        $string = str_replace(\dirname(__DIR__, 2), '...', $string);

        // Fix the watcher path when running the tests with local project VS in the phar / static
        $string = str_replace('.../src/Runner/../../tools/watcher/bin/watcher-linux-amd64', 'watcher', $string);
        $string = str_replace('/tmp/watcher-linux-amd64', 'watcher', $string);

        $string = preg_replace('{you are using v\d+.\d+.\d+.}m', 'you are using vX.Y.Z.', $string);

        return preg_replace('{castor v\d+.\d+.\d+}m', 'castor v.X.Y.Z', $string);
    }
}
