<?php

namespace OCA\ScimClient\Command\Server;

use OCA\ScimClient\AppInfo\Application;
use OCA\ScimClient\Service\ScimApiService;
use OCA\ScimClient\Service\ScimServerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Sync extends Command {
	public function __construct(
		private readonly ScimApiService $scimApiService,
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
		$this->setName(Application::APP_ID . ':server:sync')
			->addArgument('name', InputArgument::REQUIRED, 'Server name')
			->setDescription('Push all Nextcloud users and groups to an SCIM server');
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
		try {
			$name = $input->getArgument('name');
			$server = $this->scimServerService->getScimServerByName($name);

			if (!$server) {
				$output->writeln(sprintf('SCIM server %s not found.', $name));
				return Command::FAILURE;
			}

			$results = $this->scimApiService->syncScimServer($server->jsonSerialize());

			$table = new Table($output);
			$table->setHeaders(['Operation', 'User/Group ID', 'Status']);
			$rows = array_map(static fn (array $result): array => [
				$result['event'],
				$result['id'],
				$result['success'] ? 'Success' : 'Failed',
			], $results);
			$table->setRows($rows);
			$table->render();

			$status = array_count_values(array_map('intval', array_column($results, 'success')));
			$output->writeln(sprintf('Summary: %u succeeded, %u failed', $status[1], $status[0]));
		} catch (\Exception $e) {
			$output->writeln('<error>Failed to sync server</error>');
			$output->writeln($e->getMessage());
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}
}
