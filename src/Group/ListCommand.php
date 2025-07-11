<?php

namespace MuenchDev\JbManager\Group;

use MuenchDev\JbManager\ProjectManagerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListCommand extends Command
{
    private ProjectManagerService $projectManager;

    public function __construct(ProjectManagerService $projectManager)
    {
        $this->projectManager = $projectManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName("group:list")
            ->setDescription("Lists all groups.");
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $xmlPath = $this->projectManager->findRecentProjectsXml();
        if (!$xmlPath) {
            $io->error(
                "Could not find recentProjects.xml. Looked in common JetBrains config directories."
            );
            return Command::FAILURE;
        }

        $projects = $this->projectManager->getProjects();

        if (empty($projects)) {
            $io->warning("No projects found.");
            return Command::SUCCESS;
        }

        $groups = $this->projectManager->getGroups();

        $io->listing($groups);

        return Command::SUCCESS;
    }
}

