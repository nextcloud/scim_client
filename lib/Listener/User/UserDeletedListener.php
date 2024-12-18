<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\User;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class UserDeletedListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			return;
		}

		// TODO: user deleted listener
	}
}
