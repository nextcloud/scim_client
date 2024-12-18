<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\Group;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\SubAdminAddedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class SubAdminAddedListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof SubAdminAddedEvent)) {
			return;
		}

		// TODO: user added to group as admin listener
	}
}
