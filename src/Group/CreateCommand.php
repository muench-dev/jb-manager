<?php

namespace MuenchDev\JbManager\Group;

use MuenchDev\JbManager\ProjectManagerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateCommand extends Command
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
            ->setName("group:create")
            ->setDescription("Creates a new project group.")
            ->addArgument(
                "name",
                InputArgument::REQUIRED,
                "The name of the group to create."
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);
        $groupName = $input->getArgument("name");

        if ($this->projectManager->createGroup($groupName)) {
            $io->success(
                "Group '{$groupName}' created successfully (or already existed)."
            );
            return Command::SUCCESS;
        }

        $io->error(
            "Failed to create group '{$groupName}'. Check file permissions for recentProjects.xml."
        );
        return Command::FAILURE;
    }
}

