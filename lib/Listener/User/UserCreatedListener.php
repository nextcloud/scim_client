<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ScimClient\Listener\User;

use OCA\ScimClient\Service\ScimEventService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserCreatedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class UserCreatedListener implements IEventListener {

	public function __construct(
		private readonly ScimEventService $scimEventService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserCreatedEvent)) {
			return;
		}

		$params = [
			'event' => 'UserCreatedEvent',
			'user_id' => $event->getUid(),
		];

		$this->scimEventService->addScimEvent($params);
	}
}
