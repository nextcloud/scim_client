<?php

declare(strict_types=1);

namespace OCA\ScimClient\Listener\Group;

use OCA\ScimClient\Service\ScimEventService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\SubAdminAddedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class SubAdminAddedListener implements IEventListener {

	public function __construct(
		private readonly ScimEventService $scimEventService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof SubAdminAddedEvent)) {
			return;
		}

		$params = [
			'event' => 'SubAdminAddedEvent',
			'group_id' => $event->getGroup()->getGID(),
			'user_id' => $event->getUser()->getUID(),
		];

		$this->scimEventService->addScimEvent($params);
	}
}
