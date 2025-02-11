<?php

namespace Castor\Tests\Generated;

use Castor\Tests\TaskTestCase;

class FilesystemFindTest extends TaskTestCase
{
    // filesystem:find
    public function test(): void
    {
        $process = $this->runTask(['filesystem:find']);

        $this->assertSame(0, $process->getExitCode());
        $this->assertStringEqualsFile(__FILE__ . '.output.txt', $process->getOutput());
        $this->assertSame('', $process->getErrorOutput());
    }
}
