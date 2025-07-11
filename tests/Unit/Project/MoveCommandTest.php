<?php

namespace MuenchDev\JbManager\Tests\Unit\Project;

use MuenchDev\JbManager\Project\MoveCommand;
use MuenchDev\JbManager\ProjectManagerService;
use MuenchDev\JbManager\Tests\Unit\BaseTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MoveCommandTest extends BaseTestCase
{
    private ProjectManagerService $projectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectManager = new ProjectManagerService();
        $this->projectManager->setRecentProjectsXmlPath($this->tempFixturePath);
    }

    public function testExecute(): void
    {
        $application = new Application();
        $application->add(new MoveCommand($this->projectManager));

        $command = $application->find('project:move');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'project-name' => 'project-alpha',
            'group-name' => 'Group A',
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Project 'project-alpha' moved to group 'Group A'.", $output);

        $projects = $this->projectManager->getProjects();
        $alpha = current(array_filter($projects, fn($p) => $p['name'] === 'project-alpha'));
        $this->assertEquals('Group A', $alpha['group']);
    }

    public function testExecuteInteractive(): void
    {
        $application = new Application();
        $application->add(new MoveCommand($this->projectManager));

        $command = $application->find('project:move');
        $commandTester = new CommandTester($command);

        // User selects project-beta (index 1) and then Group C (index 2)
        $commandTester->setInputs(["1\n", "2\n"]);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Project 'project-beta' moved to group 'Group C'.", $output);

        $projects = $this->projectManager->getProjects();
        $beta = current(array_filter($projects, fn($p) => $p['name'] === 'project-beta'));
        $this->assertEquals('Group C', $beta['group']);
    }
}
