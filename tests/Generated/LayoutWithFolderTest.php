<?php

namespace Castor\Tests\Generated;

use Castor\Tests\TaskTestCase;

class LayoutWithFolderTest extends TaskTestCase
{
    // list
    public function test(): void
    {
        $process = $this->runTask(['list'], '{{ base }}/tests/fixtures/valid/layout-with-folder');

        $this->assertSame(0, $process->getExitCode());
        $this->assertStringEqualsFile(__FILE__ . '.output.txt', $process->getOutput());
        $this->assertSame('', $process->getErrorOutput());
    }
}
