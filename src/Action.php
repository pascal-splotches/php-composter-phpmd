<?php

namespace PHPComposter\PHPComposter\PHPMD;

use Eloquent\Pathogen\FileSystem\FileSystemPath;
use Exception;
use PHPComposter\PHPComposter\BaseAction;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Class Action
 *
 * @since 0.1.0
 *
 * @package PHPComposter\PHPComposter\PHPMD
 *
 * @author Pascal Scheepers <pascal@splotch.es>
 */
class Action extends BaseAction
{
    const EXIT_ERRORS_FOUND = 1;
    const EXIT_WITH_EXCEPTIONS = 2;

    const OS_WINDOWS = 'Windows';
    const OS_BSD = 'BSD';
    const OS_DARWIN = 'Darwin';
    const OS_SOLARIS = 'Solaris';
    const OS_LINUX = 'Linux';
    const OS_UNKNOWN = 'Unknown';

    /**
     * Check files against the rules defined in phpmd.xml
     *
     * @since 0.1.0
     */
    public function runPhpMd()
    {
        try {
            $this->checkPhpMdConfiguration();

            $process = new Process([$this->getPhpMdPath()]);
            $process->run();

            $this->write($process->getOutput());

            if (!$process->isSuccessful()) {
                $this->success("PHPMD detected no errors, allowing commit to proceed.");
            }

            $this->error("PHPMD detected errors, aborting commit!" , self::EXIT_ERRORS_FOUND);
        } catch (Exception $e) {
            $this->error(' An error occurred trying to run PHPMD: '  . PHP_EOL . $e->getMessage(), self::EXIT_WITH_EXCEPTIONS);
        }
    }

    /**
     * Build the path to the PHPMD binary
     *
     * @return string
     */
    protected function getPhpMdPath()
    {
        $root = FileSystemPath::fromString($this->root);

        $phpMdPath = $root->joinAtomSequence(
            [
                "vendor",
                "bin",
                $this->getPhpMdBinary(),
            ]
        );

        return $phpMdPath->string();
    }

    /**
     * Build the path to the PHPMD configuration
     *
     * @return string
     */
    protected function getPhpMdConfigurationPath()
    {
        $root = FileSystemPath::fromString($this->root);

        $phpMdConfigurationPath = $root->joinAtomSequence(
            [
                "phpmd.xml",
            ]
        );

        return $phpMdConfigurationPath->string();
    }

    /**
     * Return the correct binary for the current OS
     *
     * @return string
     */
    protected function getPhpMdBinary()
    {
        switch (PHP_OS_FAMILY) {
            case self::OS_WINDOWS:
                return "phpmd.bat";
                break;
            default:
                return "phpmd";
                break;
        }
    }

    /**
     * Check whether PHPMD Configuration is available
     *
     * @throws RuntimeException
     */
    protected function checkPhpMdConfiguration()
    {
        if (!file_exists($this->getPhpMdConfigurationPath())) {
            throw new RuntimeException(" PHPMD Configuration file missing");
        }
    }
}
