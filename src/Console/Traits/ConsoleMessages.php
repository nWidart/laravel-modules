<?php

namespace Nwidart\Modules\Console\Traits;

trait ConsoleMessages
{
    public function info($string, $verbosity = NULL)
    {
        return $this->line("🠲 <options=bold;>{$string} ");
    }

    public function success($string, $verbosity = NULL)
    {
        return $this->line("✅ <options=bold;fg=blue>{$string} ");
    }

    public function warning($string, $verbosity = NULL)
    {
        return $this->line("⚠️  <fg=yellow;options=bold>{$string} ");
    }

    public function critical($string, $verbosity = NULL)
    {
        return $this->line("❌ <fg=white;bg=red;options=bold>{$string} ");
    }
}
