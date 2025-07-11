<?php

namespace MuenchDev\JbManager\Tests\Unit\Group;

use MuenchDev\JbManager\Group\ListCommand;
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

        $command = $application->find('group:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Group A', $output);
        $this->assertStringContainsString('Group B', $output);
        $this->assertStringContainsString('Group C', $output);
        $this->assertStringContainsString('Group D', $output);
        $this->assertStringContainsString('Temporary', $output);
    }
}
