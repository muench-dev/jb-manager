<?php

// This is a self-contained PHP file. It requires a vendor/autoload.php
// from Composer to load the Symfony components.
if (file_exists(__DIR__ . "/vendor/autoload.php")) {
    require __DIR__ . "/vendor/autoload.php";
} else {
    echo "Vendor autoload not found. Please run 'composer require symfony/console symfony/process' in this directory.\n";
    exit(1);
}

use MuenchDev\JbManager\Group\CreateCommand as GroupCreateCommand;
use MuenchDev\JbManager\Group\DeleteCommand as GroupDeleteCommand;
use MuenchDev\JbManager\Group\ListCommand as GroupListCommand;
use MuenchDev\JbManager\Project\MoveCommand as ProjectMoveCommand;
use MuenchDev\JbManager\Project\OpenCommand as ProjectOpenCommand;
use MuenchDev\JbManager\Project\ListCommand as ProjectListCommand;
use MuenchDev\JbManager\ProjectManagerService;
use Symfony\Component\Console\Application;

// --- Application Bootstrap ---

$application = new Application("JetBrains Project Manager", "1.0.0");

// Instantiate the service and inject it into commands
$projectManager = new ProjectManagerService();
$application->add(new GroupListCommand($projectManager));
$application->add(new GroupCreateCommand($projectManager));
$application->add(new GroupDeleteCommand($projectManager));
$application->add(new ProjectListCommand($projectManager));
$application->add(new ProjectMoveCommand($projectManager));
$application->add(new ProjectOpenCommand($projectManager));

try {
    $application->run();
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
}
