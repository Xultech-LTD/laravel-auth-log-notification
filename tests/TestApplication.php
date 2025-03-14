<?php

namespace Tests;

use Illuminate\Container\Container;

class TestApplication extends Container
{
    protected array $terminatingCallbacks = [];

    public function terminating(callable $callback = null)
    {
        if (is_null($callback)) {
            foreach ($this->terminatingCallbacks as $callback) {
                $callback();
            }

            return;
        }

        $this->terminatingCallbacks[] = $callback;
    }
}