<?php

namespace Castor\Tests\Generated;

use Castor\Tests\TaskTestCase;

class CdDirectoryTest extends TaskTestCase
{
    // cd:directory
    public function test(): void
    {
        $process = $this->runTask(['cd:directory']);

        $this->assertSame(0, $process->getExitCode());
        $this->assertStringEqualsFile(__FILE__ . '.output.txt', $process->getOutput());
        $this->assertSame('', $process->getErrorOutput());
    }
}
