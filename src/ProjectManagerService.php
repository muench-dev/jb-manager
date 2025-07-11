<?php

namespace MuenchDev\JbManager;

/**
 * Service to handle the logic of finding and manipulating the recentProjects.xml file.
 * This keeps the commands clean and focused on I/O.
 */
class ProjectManagerService
{
    private ?string $recentProjectsXmlPath = null;
    private ?\DOMDocument $dom = null;
    private ?\DOMXPath $xpath = null;

    public function findRecentProjectsXml(): ?string
    {
        if ($this->recentProjectsXmlPath) {
            return $this->recentProjectsXmlPath;
        }

        $homeDir = getenv("HOME");
        if (!$homeDir) {
            return null;
        }

        $configPaths = [
            $homeDir . "/.config/JetBrains",
            $homeDir . "/.var/app/com.jetbrains.PhpStorm/config/JetBrains",
            $homeDir . "/.var/app/com.jetbrains.IntelliJ-IDEA-Ultimate/config/JetBrains",
        ];

        $latestFile = null;
        $latestTime = 0;

        foreach ($configPaths as $configPath) {
            if (!is_dir($configPath)) {
                continue;
            }

            try {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $configPath,
                        \FilesystemIterator::SKIP_DOTS
                    ),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $file) {
                    if ($file->getFilename() === "recentProjects.xml") {
                        if ($file->getMTime() > $latestTime) {
                            $latestTime = $file->getMTime();
                            $latestFile = $file->getRealPath();
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore directories we can't read
            }
        }

        $this->recentProjectsXmlPath = $latestFile;
        return $this->recentProjectsXmlPath;
    }

    private function loadXml(): bool
    {
        if ($this->dom) {
            return true;
        }

        $path = $this->findRecentProjectsXml();
        if (!$path || !file_exists($path)) {
            return false;
        }

        $this->dom = new \DOMDocument("1.0", "UTF-8");
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = true;
        if (!@$this->dom->load($path)) {
            $this->dom = null;
            return false;
        }

        $this->xpath = new \DOMXPath($this->dom);
        return true;
    }

    private function saveXml(): bool
    {
        if (!$this->dom || !$this->recentProjectsXmlPath) {
            return false;
        }
        return $this->dom->save($this->recentProjectsXmlPath) !== false;
    }

    public function getProjects(): array
    {
        if (!$this->loadXml()) {
            return [];
        }

        $projectGroupMap = [];
        $groupNodes = $this->xpath->query(
            '//component[@name="RecentProjectsManager"]/option[@name="groups"]//ProjectGroup'
        );

        foreach ($groupNodes as $groupNode) {
            $nameNode = $this->xpath->query('option[@name="name"]', $groupNode)->item(0);
            if ($nameNode) {
                $groupName = $nameNode->getAttribute('value');
                $projectPathNodes = $this->xpath->query('option[@name="projects"]/list/option', $groupNode);
                foreach ($projectPathNodes as $projectPathNode) {
                    $projectPath = $projectPathNode->getAttribute('value');
                    $projectGroupMap[$projectPath] = $groupName;
                }
            }
        }

        $projects = [];
        $entries = $this->xpath->query(
            '//component[@name="RecentProjectsManager"]/option[@name="additionalInfo"]/map/entry'
        );

        foreach ($entries as $entry) {
            $path = $entry->getAttribute("key");
            $projectName = basename($path);

            $groupName = $projectGroupMap[$path] ?? "default";

            $projects[] = [
                "name" => $projectName,
                "path" => $path,
                "group" => $groupName,
            ];
        }

        usort($projects, fn($a, $b) => strnatcasecmp($a["name"], $b["name"]));

        return $projects;
    }

    public function getGroups(): array
    {
        if (!$this->loadXml()) {
            return [];
        }

        $groups = [];
        $groupNodes = $this->xpath->query(
            '//component[@name="RecentProjectsManager"]/option[@name="groups"]//ProjectGroup'
        );

        foreach ($groupNodes as $groupNode) {
            $nameNode = $this->xpath->query('option[@name="name"]', $groupNode)->item(0);
            if ($nameNode) {
                $groups[] = $nameNode->getAttribute('value');
            }
        }

        $projects = $this->getProjects();
        foreach ($projects as $project) {
            if (!in_array($project["group"], $groups, true)) {
                $groups[] = $project["group"];
            }
        }
        sort($groups);
        return array_unique($groups);
    }

    public function createGroup(string $groupName): bool
    {
        if (!$this->loadXml()) {
            return false;
        }

        $groupsNode = $this->xpath
            ->query(
                '//component[@name="RecentProjectsManager"]/option[@name="groups"]'
            )
            ->item(0);

        if (!$groupsNode) {
            $componentNode = $this->xpath
                ->query('//component[@name="RecentProjectsManager"]')
                ->item(0);
            if (!$componentNode) {
                return false;
            }

            $optionNode = $this->dom->createElement("option");
            $optionNode->setAttribute("name", "groups");
            $groupsNode = $this->dom->createElement("list");
            $optionNode->appendChild($groupsNode);
            $componentNode->appendChild($optionNode);
        }

        $existingGroups = $this->xpath->query(
            './/ProjectGroup[option[@name="name" and @value="' . htmlspecialchars($groupName) . '"]]',
            $groupsNode
        );
        if ($existingGroups->length > 0) {
            return true;
        }

        $groupElement = $this->dom->createElement("ProjectGroup");

        $nameOption = $this->dom->createElement("option");
        $nameOption->setAttribute("name", "name");
        $nameOption->setAttribute("value", $groupName);
        $groupElement->appendChild($nameOption);

        $projectsOption = $this->dom->createElement("option");
        $projectsOption->setAttribute("name", "projects");
        $groupElement->appendChild($projectsOption);

        $listElement = $this->dom->createElement("list");
        $projectsOption->appendChild($listElement);

        $groupsNode->firstChild->appendChild($groupElement);

        return $this->saveXml();
    }

    public function moveProjectToGroup(
        string $projectName,
        string $groupName
    ): bool {
        if (!$this->loadXml()) {
            return false;
        }

        $projectPath = $this->findProjectPath($projectName);
        if (!$projectPath) {
            return false; // Project not found
        }

        // 1. Find the project's option element to move it.
        $projectOptionNode = $this->xpath->query(
            '//component[@name="RecentProjectsManager"]/option[@name="groups"]//ProjectGroup/option[@name="projects"]/list/option[@value="' . $projectPath . '"]'
        )->item(0);

        // If the project is not in any group, it's in "default".
        // We might need to create a new element for it if moving to a non-default group.
        if (!$projectOptionNode) {
            $projectOptionNode = $this->dom->createElement('option');
            $projectOptionNode->setAttribute('value', $projectPath);
        } else {
            // Remove from its current parent (the old group's list)
            $projectOptionNode->parentNode->removeChild($projectOptionNode);
        }

        // 2. Add project to the new group.
        if ($groupName !== 'default') {
            // Ensure the target group exists.
            $this->createGroup($groupName);

            // Find the target group node itself.
            $targetGroupNode = $this->xpath->query(
                '//component[@name="RecentProjectsManager"]/option[@name="groups"]//ProjectGroup[option[@name="name" and @value="' . $groupName . '"]]'
            )->item(0);

            if ($targetGroupNode) {
                // Find the projects list within this group.
                $targetGroupProjectsList = $this->xpath->query('option[@name="projects"]/list', $targetGroupNode)->item(0);

                // If the projects list doesn't exist, create it.
                if (!$targetGroupProjectsList) {
                    $projectsOption = $this->dom->createElement('option');
                    $projectsOption->setAttribute('name', 'projects');

                    $listElement = $this->dom->createElement('list');
                    $projectsOption->appendChild($listElement);

                    $targetGroupNode->appendChild($projectsOption);
                    $targetGroupProjectsList = $listElement;
                }

                // Append the project to the new group's list.
                $targetGroupProjectsList->appendChild($projectOptionNode);
            }
        }
        // If moving to 'default', we've already removed it from its old group, which is all that's needed.

        return $this->saveXml();
    }

    public function findProjectPath(string $projectName): ?string
    {
        $projects = $this->getProjects();
        foreach ($projects as $project) {
            if (strcasecmp($project["name"], $projectName) === 0) {
                return $project["path"];
            }
        }
        return null;
    }

    private function findProjectEntry(string $projectName): ?\DOMElement
    {
        $projectPath = $this->findProjectPath($projectName);
        if (!$projectPath) {
            return null;
        }

        $encodedPath = str_replace(" ", "%20", $projectPath);

        $entryNode = $this->xpath
            ->query(
                '//component[@name="RecentProjectsManager"]/option[@name="additionalInfo"]/map/entry[@key="' .
                    $projectPath .
                    '"]'
            )
            ->item(0);
        if (!$entryNode) {
            $entryNode = $this->xpath
                ->query(
                    '//component[@name="RecentProjectsManager"]/option[@name="additionalInfo"]/map/entry[@key="' .
                        $encodedPath .
                        '"]'
                )
                ->item(0);
        }

        return $entryNode;
    }
}
