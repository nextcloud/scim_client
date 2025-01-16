<?php

namespace OCA\ScimClient\Command\Server;

use OCA\ScimClient\AppInfo\Application;
use OCA\ScimClient\Service\ScimServerService;
use OCA\ScimClient\Service\ScimSyncRequestService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Register extends Command {
	public function __construct(
		private readonly ScimServerService $scimServerService,
		private readonly ScimSyncRequestService $scimSyncRequestService,
	) {
		parent::__construct();
	}

	/**
	 * Configure the command
	 *
	 * @return void
	 */
	protected function configure(): void {
		$this->setName(Application::APP_ID . ':server:register')
			->addArgument('name', InputArgument::REQUIRED, 'Server name')
			->addArgument('url', InputArgument::REQUIRED, 'Server URL')
			->addArgument('api_key', InputArgument::REQUIRED, 'Server API key')
			->setDescription('Register SCIM server');
	}

	/**
	 * Execute the command
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$params = [
			'name' => $input->getArgument('name'),
			'url' => $input->getArgument('url'),
			'api_key' => $input->getArgument('api_key'),
		];

		if (!str_starts_with($params['url'], 'http://') && !str_starts_with($params['url'], 'https://')) {
			$output->writeln('Failed to register SCIM server. URL must start with `http://` or `https://`.');
			return Command::FAILURE;
		}
		$params['url'] = rtrim($params['url'], '/');

		$server = $this->scimServerService->registerScimServer($params);
		if (!$server) {
			$output->writeln('Failed to register SCIM server.');
			return Command::FAILURE;
		}
		$output->writeln('SCIM server successfully registered.');

		$syncParams = ['server_id' => $server->getId()];
		$request = $this->scimSyncRequestService->addScimSyncRequest($syncParams);
		if (!$request) {
			$output->writeln('Warning: failed to initialize server sync.');
		}

		return Command::SUCCESS;
	}
}
