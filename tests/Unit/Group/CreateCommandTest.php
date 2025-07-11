<?php

namespace MuenchDev\JbManager\Tests\Unit\Group;

use MuenchDev\JbManager\Group\CreateCommand;
use MuenchDev\JbManager\ProjectManagerService;
use MuenchDev\JbManager\Tests\Unit\BaseTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateCommandTest extends BaseTestCase
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
        $application->add(new CreateCommand($this->projectManager));

        $command = $application->find('group:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'name' => 'New Group',
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Group 'New Group' created successfully", $output);

        $groups = $this->projectManager->getGroups();
        $this->assertContains('New Group', $groups);
    }
}
