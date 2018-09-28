# PHP Composter PHPMD 

![standard-readme compliant](https://img.shields.io/badge/standard--readme-OK-green.svg?style=flat-square)

Automatically install a git pre-commit hook to check your PHP files upon each commit to make sure they follow the rules defined in `phpmd.xml`. 

## Table Of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Built With](#built-with)
- [Contributing](#contributing)
- [Versioning](#versioning)
- [Authors](#authors)
- [License](#license)

## Installation

Add as a development requirement to your `composer.json`:

```bash
composer require --dev pascal-splotches/php-composter-phpmd
```

## Usage

It automatically works whenever you execute a `git commit`.

## Configuration

You need to add a configuration section to your `phpmd.xml` ruleset with the correct flags and paths:

```xml
<configuration>
    <!-- Required -->
    <source>
        <path>src</path>
        <!-- <path></path>... -->
    </source>
    
    <!-- Optional -->
    <exclude>
        <path>docs</path>
        <!-- <path></path>... -->
    </exclude>
    
    <!-- Required -->
    <output mode="text"/>
    
    <!-- Optional -->
    <minimum-priority value="5"/>
    <report file="report.txt"/>
    
    <!-- Optional -->
    <suffixes>
        <suffix>php</suffix>
        <!-- <suffix></suffix>... -->
    </suffixes>
    
    <!-- Optional -->
    <strict/>
</configuration>
```

## Built With

- [Composter](https://github.com/php-composter/php-composter)
- [PHP Mess Detector](https://phpmd.org/)

## Contributing

All feedback, bug reports and pull requests are welcomed.

## Versioning

We use [SemVer](https://semver.org/) for versioning. For the versions available, see the [releases on this repository](https://github.com/pascal-splotches/php-composter-phpmd/releases).

## Authors

- Pascal Scheepers <pascal@splotch.es>

## License

This project is licensed under the GPL v3 License - see the [LICENSE](./LICENSE) file for details.
