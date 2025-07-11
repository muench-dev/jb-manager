<?php

namespace MuenchDev\JbManager\Project;

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
            ->setName("project:list")
            ->setDescription(
            "Lists all recent JetBrains projects."
        );
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
        $io->comment("Using config: " . $xmlPath);

        $projects = $this->projectManager->getProjects();

        if (empty($projects)) {
            $io->warning("No projects found.");
            return Command::SUCCESS;
        }

        $groupedProjects = [];
        foreach ($projects as $project) {
            $groupedProjects[$project["group"]][] = $project;
        }

        ksort($groupedProjects);

        $scriptName = basename($_SERVER["argv"][0]);

        foreach ($groupedProjects as $groupName => $projectsInGroup) {
            $io->section("Group: " . $groupName);
            $tableRows = [];
            foreach ($projectsInGroup as $project) {
                $tableRows[] = [$project["name"], $project["path"]];
            }
            $io->table(
                ["Project Name", "Path"],
                $tableRows
            );
        }

        return Command::SUCCESS;
    }
}

