<?php

namespace MuenchDev\JbManager\Tests\Unit\Project;

use MuenchDev\JbManager\Project\OpenCommand;
use MuenchDev\JbManager\ProjectManagerService;
use MuenchDev\JbManager\Tests\Unit\BaseTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class OpenCommandTest extends BaseTestCase
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
        $application->add(new OpenCommand($this->projectManager));

        $command = $application->find('project:open');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'project-name' => 'project-alpha',
            '--ide' => 'echo', // Use "echo" to prevent actual IDE launch
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $expectedPath = getenv('HOME') . '/Workspaces/project-alpha';
        $this->assertStringContainsString($expectedPath, $output);
        $this->assertStringContainsString("Launch command for 'project-alpha' has been sent.", $output);
    }

    public function testExecuteProjectNotFound(): void
    {
        $application = new Application();
        $application->add(new OpenCommand($this->projectManager));

        $command = $application->find('project:open');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'project-name' => 'non-existent-project',
        ]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Project 'non-existent-project' not found.", $output);
    }
}
