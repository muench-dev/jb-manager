<?php

namespace MuenchDev\JbManager\Group;

use MuenchDev\JbManager\ProjectManagerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteCommand extends Command
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
            ->setName("group:delete")
            ->setDescription("Deletes a project group.")
            ->addArgument(
                "name",
                InputArgument::OPTIONAL,
                "The name of the group to delete."
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);
        $groupName = $input->getArgument("name");

        if (!$groupName) {
            $groups = $this->projectManager->getGroups();
            $groupName = $io->choice(
                "Please select the group to delete",
                $groups
            );
        }

        // Confirm deletion
        if (!$io->confirm("Are you sure you want to delete the group '{$groupName}'?", false)) {
            $io->note("Operation cancelled.");
            return Command::SUCCESS;
        }

        if ($this->projectManager->deleteGroup($groupName)) {
            $io->success(
                "Group '{$groupName}' deleted successfully."
            );
            return Command::SUCCESS;
        }

        $io->error(
            "Failed to delete group '{$groupName}'. The group may not exist."
        );
        return Command::FAILURE;
    }
}
