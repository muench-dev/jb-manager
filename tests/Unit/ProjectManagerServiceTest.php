<?php

namespace MuenchDev\JbManager\Tests\Unit;

use MuenchDev\JbManager\ProjectManagerService;

class ProjectManagerServiceTest extends BaseTestCase
{
    private ProjectManagerService $projectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectManager = new ProjectManagerService();
        $this->projectManager->setRecentProjectsXmlPath($this->tempFixturePath);
    }

    public function testFindRecentProjectsXml(): void
    {
        $path = $this->projectManager->findRecentProjectsXml();
        $this->assertEquals($this->tempFixturePath, $path);
    }

    public function testGetProjects(): void
    {
        $projects = $this->projectManager->getProjects();
        $this->assertCount(4, $projects);

        $projectNames = array_map(fn($p) => $p['name'], $projects);
        sort($projectNames);

        $this->assertEquals(['project-alpha', 'project-beta', 'project-delta', 'project-gamma'], $projectNames);

        $alpha = current(array_filter($projects, fn($p) => $p['name'] === 'project-alpha'));
        $this->assertEquals('Group B', $alpha['group']);
    }

    public function testGetGroups(): void
    {
        $groups = $this->projectManager->getGroups();
        $this->assertCount(5, $groups);
        $this->assertContains('Group A', $groups);
        $this->assertContains('Group B', $groups);
        $this->assertContains('Group C', $groups);
        $this->assertContains('Group D', $groups);
        $this->assertContains('Temporary', $groups);
    }

    public function testCreateGroup(): void
    {
        $this->projectManager->createGroup('New Group');
        $groups = $this->projectManager->getGroups();
        $this->assertContains('New Group', $groups);

        // Test idempotency
        $this->projectManager->createGroup('New Group');
        $groupsAfter = $this->projectManager->getGroups();
        $this->assertCount(count($groups), $groupsAfter);
    }

    public function testMoveProjectToGroup(): void
    {
        $this->projectManager->moveProjectToGroup('project-alpha', 'Group A');
        $projects = $this->projectManager->getProjects();
        $alpha = current(array_filter($projects, fn($p) => $p['name'] === 'project-alpha'));
        $this->assertEquals('Group A', $alpha['group']);
    }

    public function testFindProjectPath(): void
    {
        $path = $this->projectManager->findProjectPath('project-gamma');
        $this->assertEquals('$USER_HOME$/Workspaces/project-gamma', $path);
    }

    public function testFindProjectPathCaseInsensitive(): void
    {
        $path = $this->projectManager->findProjectPath('PrOjEcT-GaMmA');
        $this->assertEquals('$USER_HOME$/Workspaces/project-gamma', $path);
    }

    public function testFindProjectPathNotFound(): void
    {
        $path = $this->projectManager->findProjectPath('non-existent-project');
        $this->assertNull($path);
    }

    public function testDeleteGroup(): void
    {
        // First verify the group exists
        $groups = $this->projectManager->getGroups();
        $this->assertContains('Group A', $groups);

        // Delete the group
        $result = $this->projectManager->deleteGroup('Group A');
        $this->assertTrue($result);

        // Verify the group no longer exists
        $groupsAfter = $this->projectManager->getGroups();
        $this->assertNotContains('Group A', $groupsAfter);
    }

    public function testDeleteNonExistentGroup(): void
    {
        // Try to delete a group that doesn't exist
        $result = $this->projectManager->deleteGroup('Non-Existent-Group');
        $this->assertFalse($result);
    }
}
