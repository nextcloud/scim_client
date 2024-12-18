<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\Group;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupCreatedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class GroupCreatedListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof GroupCreatedEvent)) {
			return;
		}

		// TODO: group created listener
	}
}
