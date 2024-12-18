<?php

declare(strict_types=1);

namespace OCA\ScimClient\AppInfo;

use OCA\ScimClient\Listener\Group\GroupChangedListener;
use OCA\ScimClient\Listener\Group\GroupCreatedListener;
use OCA\ScimClient\Listener\Group\GroupDeletedListener;
use OCA\ScimClient\Listener\Group\SubAdminAddedListener;
use OCA\ScimClient\Listener\Group\SubAdminRemovedListener;
use OCA\ScimClient\Listener\Group\UserAddedListener;
use OCA\ScimClient\Listener\Group\UserRemovedListener;
use OCA\ScimClient\Listener\User\PasswordUpdatedListener;
use OCA\ScimClient\Listener\User\UserChangedListener;
use OCA\ScimClient\Listener\User\UserCreatedListener;
use OCA\ScimClient\Listener\User\UserDeletedListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Group\Events\GroupChangedEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\Group\Events\SubAdminAddedEvent;
use OCP\Group\Events\SubAdminRemovedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'scim_client';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(GroupChangedEvent::class, GroupChangedListener::class);
		$context->registerEventListener(GroupCreatedEvent::class, GroupCreatedListener::class);
		$context->registerEventListener(GroupDeletedEvent::class, GroupDeletedListener::class);
		$context->registerEventListener(PasswordUpdatedEvent::class, PasswordUpdatedListener::class);
		$context->registerEventListener(SubAdminAddedEvent::class, SubAdminAddedListener::class);
		$context->registerEventListener(SubAdminRemovedEvent::class, SubAdminRemovedListener::class);
		$context->registerEventListener(UserAddedEvent::class, UserAddedListener::class);
		$context->registerEventListener(UserChangedEvent::class, UserChangedListener::class);
		$context->registerEventListener(UserCreatedEvent::class, UserCreatedListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
		$context->registerEventListener(UserRemovedEvent::class, UserRemovedListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
