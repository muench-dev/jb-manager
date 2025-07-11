<?php

namespace MuenchDev\JbManager\Tests\Unit\Project;

use MuenchDev\JbManager\Project\ListCommand;
use MuenchDev\JbManager\ProjectManagerService;
use MuenchDev\JbManager\Tests\Unit\BaseTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends BaseTestCase
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
        $application->add(new ListCommand($this->projectManager));

        $command = $application->find('project:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Group: Group A', $output);
        $this->assertStringContainsString('project-gamma', $output);
        $this->assertStringContainsString('Group: Group B', $output);
        $this->assertStringContainsString('project-alpha', $output);
        $this->assertStringContainsString('Group: Group C', $output);
        $this->assertStringContainsString('project-delta', $output);
        $this->assertStringContainsString('Group: Group D', $output);
        $this->assertStringContainsString('project-beta', $output);
    }
}
