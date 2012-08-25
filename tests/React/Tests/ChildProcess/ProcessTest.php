<?php

namespace React\Tests\ChildProcess;

use React\ChildProcess\Process;

class ProcessTest extends \PHPUnit_Framework_TestCase
{
    const SIGNAL_CODE_SIGTERM = 15;

    public function testGetCommand()
    {
        $process = $this->createProcess('echo foo bar');

        $this->assertSame('echo foo bar', $process->getCommand());
    }

    public function testIsRunning()
    {
        $process = $this->createProcess('sleep 1');

        $this->assertTrue($process->isRunning());

        return $process;
    }

    /**
     * @depends testIsRunning
     */
    public function testGetExitCodeWhenRunning($process)
    {
        $this->assertNull($process->getExitCode());
    }

    /**
     * @depends testIsRunning
     */
    public function testGetSignalCodeWhenRunning($process)
    {
        $this->assertNull($process->getSignalCode());
    }

    public function testHandleExit()
    {
        $process = $this->createProcess('sleep 1');

        $exitIsCalled = false;
        $closeIsCalled = false;

        $process->on('exit', function ($exitCode, $signalCode) use (&$exitIsCalled) {
            $exitIsCalled = true;
        });
        $process->on('close', function ($exitCode, $signalCode) use (&$closeIsCalled) {
            $closeIsCalled = true;
        });


        $process->handleExit(0, null);

        $this->assertTrue($exitIsCalled);
        $this->assertTrue($closeIsCalled);

        return $process;
    }

    /**
     * @depends testHandleExit
     */
    public function testGetExitCode($process)
    {
        $this->assertEquals(0, $process->getExitCode());
    }

    /**
     * @depends testHandleExit
     */
    public function testGetSignalCode($process)
    {
        $this->assertNull($process->getSignalCode());
    }

    /**
     * @depends testHandleExit
     */
    public function testIsRunningWhenExited($process)
    {
        $this->assertFalse($process->isRunning());
    }

    public function testHandleExitWhenTerminated()
    {
        $process = $this->createProcess('sleep 1');

        $exitIsCalled = false;

        $process->on('exit', function ($exitCode, $signalCode) use (&$exitIsCalled) {
            $exitIsCalled = true;
        });

        $process->handleExit(null, self::SIGNAL_CODE_SIGTERM);

        $this->assertTrue($exitIsCalled);

        return $process;
    }

    /**
     * @depends testHandleExitWhenTerminated
     */
    public function testGetExitCodeWhenTerminated($process)
    {
        $this->assertNull($process->getExitCode());
    }

    /**
     * @depends testHandleExitWhenTerminated
     */
    public function testGetSignalCodeWhenTerminated($process)
    {
        $this->assertEquals(self::SIGNAL_CODE_SIGTERM, $process->getSignalCode());
    }

    /**
     * @depends testHandleExitWhenTerminated
     */
    public function testIsRunningWhenTerminated($process)
    {
        $this->assertFalse($process->isRunning());
    }

    public function testExitEventCanBeCalledOnlyOnce()
    {
        $process = $this->createProcess('php -v');

        $counter = 0;

        $process->on('exit', function ($status) use (&$counter) {
            $counter++;
        });

        $process->handleExit(0, null);
        $process->handleExit(0, null);

        $this->assertEquals(1, $counter);
    }

    public function testHandleCloseExitEvent()
    {
        $process = $this->createProcess("php '-r' 'exit(0);'");

        $capturedExitStatus = null;

        $process->on('close', function ($status) use (&$capturedExitStatus) {
            $capturedExitStatus = $status;
        });

        $process->exits();

        $this->assertEquals(0, $capturedExitStatus);
    }

    public function testHandleCloseExitEventStatus1()
    {
        $process = $this->createProcess("php '-r' 'exit(1);'");

        $capturedExitStatus = null;

        $process->on('close', function ($status) use (&$capturedExitStatus) {
            $capturedExitStatus = $status;
        });

        $process->exits();

        $this->assertEquals(1, $capturedExitStatus);
    }

    public function testCloseEventCanBeCalledOnlyOnce()
    {
        $process = $this->createProcess('php -v');

        $counter = 0;

        $process->on('close', function ($status) use (&$counter) {
            $counter++;
        });

        $process->handleExit(0, null);
        $process->handleExit(0, null);

        $this->assertEquals(1, $counter);
    }

    private function createProcess($command)
    {
        return new Process(
            $this->createProcessStream($command),
            $this->getMock('React\Stream\WritableStreamInterface'),
            $this->getMock('React\Stream\ReadableStreamInterface'),
            $this->getMock('React\Stream\ReadableStreamInterface')
        );
    }

    private function createProcessStream($command)
    {
        $fdSpecs = array(
            array('pipe', 'r'),
            array('pipe', 'w'),
            array('pipe', 'w'),
        );
        return proc_open($command, $fdSpecs, $pipes);
    }
}
