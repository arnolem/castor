<?php

namespace Castor\Tests\Generated;

use Castor\Tests\TaskTestCase;

class ImportFileNotExistTest extends TaskTestCase
{
    // no task
    public function test(): void
    {
        $process = $this->runTask([], '{{ base }}/tests/fixtures/broken/import-file-not-exist', needRemote: true);

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringEqualsFile(__FILE__ . '.output.txt', $process->getOutput());
        $this->assertStringEqualsFile(__FILE__ . '.err.txt', $process->getErrorOutput());
    }
}
