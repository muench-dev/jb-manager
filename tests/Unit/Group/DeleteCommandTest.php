<?php

namespace MuenchDev\JbManager\Tests\Unit\Group;

use MuenchDev\JbManager\Group\DeleteCommand;
use MuenchDev\JbManager\ProjectManagerService;
use MuenchDev\JbManager\Tests\Unit\BaseTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteCommandTest extends BaseTestCase
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
        $application->add(new DeleteCommand($this->projectManager));

        $command = $application->find('group:delete');
        $commandTester = new CommandTester($command);

        // Confirm deletion with "yes"
        $commandTester->setInputs(['yes']);

        $commandTester->execute([
            'name' => 'Group A',
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Group 'Group A' deleted successfully", $output);

        $groups = $this->projectManager->getGroups();
        $this->assertNotContains('Group A', $groups);
    }

    public function testExecuteCancelled(): void
    {
        $application = new Application();
        $application->add(new DeleteCommand($this->projectManager));

        $command = $application->find('group:delete');
        $commandTester = new CommandTester($command);

        // Cancel deletion with "no"
        $commandTester->setInputs(['no']);

        $commandTester->execute([
            'name' => 'Group B',
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Operation cancelled", $output);

        $groups = $this->projectManager->getGroups();
        $this->assertContains('Group B', $groups);
    }

    public function testExecuteInteractive(): void
    {
        $application = new Application();
        $application->add(new DeleteCommand($this->projectManager));

        $command = $application->find('group:delete');
        $commandTester = new CommandTester($command);

        // Select Group C (index 2) and confirm with "yes"
        $commandTester->setInputs(['2', 'yes']);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Group 'Group C' deleted successfully", $output);

        $groups = $this->projectManager->getGroups();
        $this->assertNotContains('Group C', $groups);
    }
}
