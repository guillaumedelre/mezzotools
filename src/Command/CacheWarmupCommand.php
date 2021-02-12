<?php

namespace App\Command;

use App\Service\CacheWarmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheWarmupCommand extends Command
{
    protected static $defaultName = 'app:cache:warmup';
    protected static $defaultDescription = 'Download locally the needed data from JIRA.';

    protected CacheWarmer $cacheWarmer;

    public function __construct(CacheWarmer $cacheWarmer)
    {
        parent::__construct();
        $this->cacheWarmer = $cacheWarmer;
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->cacheWarmer->refresh()) {
            $io->success("The local cache of the projects list is updated");
        } else {
            $io->warning("The local cache of the projects list is outdated");
        }

        return Command::SUCCESS;
    }
}
