<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\Group;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupChangedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class GroupChangedListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof GroupChangedEvent)) {
			return;
		}

		// TODO: group updated listener
	}
}
