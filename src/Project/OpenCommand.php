<?php

namespace MuenchDev\JbManager\Project;

use MuenchDev\JbManager\ProjectManagerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class OpenCommand extends Command
{
    private ProjectManagerService $projectManager;

    public function __construct(ProjectManagerService $projectManager)
    {
        $this->projectManager = $projectManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("project:open")
            ->setDescription(
                "Opens a project with the associated JetBrains IDE command-line tool."
            )
            ->addArgument(
                "project-name",
                InputArgument::OPTIONAL,
                "The name of the project to open."
            )
            ->addOption(
                "ide",
                "i",
                InputOption::VALUE_REQUIRED,
                'The IDE command (e.g., idea, pstorm, clion). Defaults to "idea".',
                "idea"
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);
        $projectName = $input->getArgument("project-name");
        $ideCommand = $input->getOption("ide");

        // If project name is not provided, ask interactively
        if (!$projectName) {
            $projects = $this->projectManager->getProjects();
            if (empty($projects)) {
                $io->error("No projects found.");
                return Command::FAILURE;
            }
            $choices = array_map(fn($p) => $p['name'], $projects);
            $projectName = $io->choice('Select a project to open', $choices);
        }

        $projectPath = $this->projectManager->findProjectPath($projectName);

        if (!$projectPath) {
            $io->error("Project '{$projectName}' not found.");
            return Command::FAILURE;
        }

        // Replace JetBrains variables in the project path
        $projectPath = $this->resolvePathVariables($projectPath);

        $io->writeln(
            "Attempting to open '{$projectPath}' with '{$ideCommand}'..."
        );

        $command = [
            "nohup",
            $ideCommand,
            $projectPath,
            ">",
            "/dev/null",
            "2>&1",
            "&",
        ];

        $process = Process::fromShellCommandline(implode(" ", $command));
        $process->disableOutput();
        $process->run();

        $io->success("Launch command for '{$projectName}' has been sent.");

        return Command::SUCCESS;
    }

    /**
     * Replace JetBrains-style variables in the path with real values.
     */
    private function resolvePathVariables(string $path): string
    {
        $replacements = [
            '$USER_HOME$' => getenv('HOME') ?: (getenv('USERPROFILE') ?: ''),
            // Add more variable replacements here if needed
        ];
        return strtr($path, $replacements);
    }
}
