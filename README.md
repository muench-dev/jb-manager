# JB Manager

JB Manager is a PHP command-line tool for managing projects and groups, designed to work seamlessly with JetBrains tools. It provides commands to list, create, delete, move, and open projects and groups, making it easier to organize and handle multiple projects efficiently.

## Features
- List, create, and delete groups
- List, move, and open projects
- Simple CLI interface
- Easily extensible
- Designed for integration with JetBrains IDEs (e.g., PhpStorm, IntelliJ IDEA)

## Requirements
- PHP 7.4 or higher
- Composer (for dependency management)
- JetBrains IDE (optional, for project opening integration)

## Installation

Clone the repository and install dependencies:

```bash
git clone https://github.com/muench-dev/jb-manager.git
cd jb-manager
composer install
```

Alternatively, use the pre-built PHAR:

```bash
php jb-manager.phar
```

You can also compile your own PHAR using the [box](https://github.com/box-project/box) tool:

```bash
box compile
```

This will generate the `jb-manager.phar` file in the project root.

## Usage

Run the CLI tool:

```bash
php cli.php [command] [options]
```

Or, if using the PHAR:

```bash
php jb-manager.phar [command] [options]
```

### Example Commands

- List all groups:
  ```bash
  php cli.php group:list
  ```
- Create a new group:
  ```bash
  php cli.php group:create <group-name>
  ```
- Delete a group:
  ```bash
  php cli.php group:delete <group-name>
  ```
- List all projects:
  ```bash
  php cli.php project:list
  ```
- Move a project:
  ```bash
  php cli.php project:move <project-name> <target-group>
  ```
- Open a project:
  ```bash
  php cli.php project:open <project-name>
  ```

## Testing

Run the test suite with PHPUnit:

```bash
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please open issues or submit pull requests.

## License

This project is licensed under the MIT License.
