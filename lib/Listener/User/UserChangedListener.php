<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\User;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserChangedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class UserChangedListener implements IEventListener {

	public function handle(Event $event): void {
		if (!($event instanceof UserChangedEvent)) {
			return;
		}

		// TODO: user updated listener
	}
}
