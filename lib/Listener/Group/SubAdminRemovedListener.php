<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\Group;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\SubAdminRemovedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class SubAdminRemovedListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof SubAdminRemovedEvent)) {
			return;
		}

		// TODO: group admin user removed from group listener
	}
}
