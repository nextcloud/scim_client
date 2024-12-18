<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\Group;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserRemovedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class UserRemovedListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof UserRemovedEvent)) {
			return;
		}

		// TODO: user removed from group listener
	}
}
