<?php

namespace Nwidart\Modules\Actuator;

use Illuminate\Console\Scheduling\Event;
use WeakMap;

/**
 * The module that scheduled each event, for the "module" tag of the actuator's scheduledtasks endpoint.
 *
 * ModuleServiceProvider claims the events that configureSchedules() adds. Events scheduled
 * elsewhere, such as in routes/console.php, have no module. The map holds the events weakly,
 * so a rebuilt Schedule leaves no stale entry behind.
 *
 * @internal
 */
final class ScheduledTaskModules
{
    /**
     * @var WeakMap<Event, string>
     */
    private WeakMap $modules;

    public function __construct()
    {
        $this->modules = new WeakMap;
    }

    /**
     * @param  iterable<Event>  $events
     */
    public function claim(iterable $events, string $module): void
    {
        foreach ($events as $event) {
            $this->modules[$event] = $module;
        }
    }

    public function moduleOf(Event $event): ?string
    {
        return $this->modules[$event] ?? null;
    }

    /**
     * The tags of one task: ['module' => 'Blog'], or none.
     *
     * @return array<string, string>
     */
    public function tags(Event $event): array
    {
        $module = $this->moduleOf($event);

        return $module === null ? [] : ['module' => $module];
    }
}
