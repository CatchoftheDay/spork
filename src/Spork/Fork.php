<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) 2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork;

use Spork\Deferred\Deferred;
use Spork\Deferred\DeferredInterface;
use Spork\Exception\ForkException;

class Fork implements DeferredInterface
{
    private $defer;
    private $pid;
    private $fifo;
    private $debug;
    private $name;
    private $status;
    private $result;
    private $output;
    private $error;

    public function __construct($pid, Fifo $fifo, $debug = false)
    {
        $this->defer = new Deferred();
        $this->pid   = $pid;
        $this->fifo  = $fifo;
        $this->debug = $debug;
        $this->name  = '<anonymous>';
    }

    /**
     * Assign a name to the current fork (useful for debugging).
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function wait($hang = true)
    {
        if ($this->pid === pcntl_waitpid($this->pid, $this->status, ($hang ? 0 : WNOHANG) | WUNTRACED)) {
            list($this->result, $this->output, $this->error) = $this->fifo->receive();
            $this->fifo->close();

            if ($this->debug && $this->error) {
                throw new ForkException($this->name, $this->pid, $this->error);
            }
        }
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getError()
    {
        return $this->error;
    }

    public function isSuccessful()
    {
        return 0 === $this->getExitStatus();
    }

    public function isExited()
    {
        return null !== $this->status && pcntl_wifexited($this->status);
    }

    public function isStopped()
    {
        return null !== $this->status && pcntl_wifstopped($this->status);
    }

    public function isSignaled()
    {
        return null !== $this->status && pcntl_wifsignaled($this->status);
    }

    public function getExitStatus()
    {
        if (null !== $this->status) {
            return pcntl_wexitstatus($this->status);
        }
    }

    public function getTermSignal()
    {
        if (null !== $this->status) {
            return pcntl_wtermsig($this->status);
        }
    }

    public function getStopSignal()
    {
        if (null !== $this->status) {
            return pcntl_wstopsig($this->status);
        }
    }

    public function getState()
    {
        return $this->defer->getState();
    }

    public function always($callback)
    {
        $this->defer->always($callback);

        return $this;
    }

    public function done($callback)
    {
        $this->defer->done($callback);

        return $this;
    }

    public function fail($callback)
    {
        $this->defer->fail($callback);

        return $this;
    }

    public function then($done, $fail = null)
    {
        $this->defer->then($done, $fail);

        return $this;
    }

    public function resolve()
    {
        $args = func_get_args();
        array_unshift($args, $this);

        call_user_func_array(array($this->defer, 'resolve'), $args);

        return $this;
    }

    public function reject()
    {
        $args = func_get_args();
        array_unshift($args, $this);

        call_user_func_array(array($this->defer, 'reject'), $args);

        return $this;
    }
}
