<?php

namespace MuenchDev\JbManager\Project;

use MuenchDev\JbManager\ProjectManagerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MoveCommand extends Command
{
    private ProjectManagerService $projectManager;

    public function __construct(ProjectManagerService $projectManager)
    {
        $this->projectManager = $projectManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("project:move")
            ->setDescription("Moves a project to a specified group.")
            ->addArgument(
                "project-name",
                InputArgument::OPTIONAL,
                "The name of the project to move."
            )
            ->addArgument(
                "group-name",
                InputArgument::OPTIONAL,
                "The name of the target group."
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);
        $projectName = $input->getArgument("project-name");
        $groupName = $input->getArgument("group-name");

        if (!$projectName) {
            $projects = $this->projectManager->getProjects();
            $projectChoices = [];
            foreach ($projects as $project) {
                $projectChoices[] = "{$project['name']} [{$project['group']}]";
            }
            $selectedChoice = $io->choice(
                "Please select the project to move",
                $projectChoices
            );

            preg_match('/^(.*) \[.*\]$/', $selectedChoice, $matches);
            $projectName = $matches[1] ?? $selectedChoice;
        }

        if (!$groupName) {
            $groups = $this->projectManager->getGroups();
            $groupName = $io->choice(
                "Please select the target group",
                $groups
            );
        }

        if (
            $this->projectManager->moveProjectToGroup($projectName, $groupName)
        ) {
            $io->success(
                "Project '{$projectName}' moved to group '{$groupName}'."
            );
            return Command::SUCCESS;
        }

        $io->error(
            "Could not move project. Did you spell the project name correctly? (check with 'list' command)"
        );
        return Command::FAILURE;
    }
}
