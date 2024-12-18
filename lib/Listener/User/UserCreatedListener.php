<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\User;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserCreatedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class UserCreatedListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof UserCreatedEvent)) {
			return;
		}

		// TODO: user created listener
	}
}
