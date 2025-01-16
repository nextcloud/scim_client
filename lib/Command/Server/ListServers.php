<?php

namespace OCA\ScimClient\Command\Server;

use OCA\ScimClient\AppInfo\Application;
use OCA\ScimClient\Service\ScimServerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListServers extends Command {
	public function __construct(
		private readonly ScimServerService $scimServerService,
	) {
		parent::__construct();
	}

	/**
	 * Configure the command
	 *
	 * @return void
	 */
	protected function configure(): void {
		$this->setName(Application::APP_ID . ':server:list')
			->setDescription('List registered SCIM servers');
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
		$servers = $this->scimServerService->getRegisteredScimServers();
		if (!$servers) {
			$output->writeln('No registered SCIM servers.');
			goto end;
		}

		$output->writeln('Registered SCIM servers:');
		$table = new Table($output);
		$table->setHeaders(['Name', 'URL']);
		$rows = array_map(static fn (array $server): array => [
			$server['name'],
			$server['url'],
		], $servers);
		$table->setRows($rows);
		$table->render();

		end:
		return Command::SUCCESS;
	}
}
