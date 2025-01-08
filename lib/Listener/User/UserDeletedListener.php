<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\User;

use OCA\ScimClient\Service\ScimEventService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class UserDeletedListener implements IEventListener {

	public function __construct(
		private readonly ScimEventService $scimEventService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserDeletedEvent)) {
			return;
		}

		$params = [
			'event' => 'UserDeletedEvent',
			'user_id' => $event->getUser()->getUID(),
		];

		$this->scimEventService->addScimEvent($params);
	}
}
