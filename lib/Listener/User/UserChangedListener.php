<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\User;

use OCA\ScimClient\Service\ScimEventService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserChangedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class UserChangedListener implements IEventListener {

	public function __construct(
		private readonly ScimEventService $scimEventService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserChangedEvent)) {
			return;
		}

		$params = [
			'event' => 'UserChangedEvent',
			'user_id' => $event->getUser()->getUID(),
			'feature' => $event->getFeature(),
			'value' => $event->getValue(),
		];

		$this->scimEventService->addScimEvent($params);
	}
}
