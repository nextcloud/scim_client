<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\Group;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class GroupDeletedListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof GroupDeletedEvent)) {
			return;
		}

		// TODO: group deleted listener
	}
}
