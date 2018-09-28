<?php

namespace PHPComposter\PHPComposter\PHPMD;

use DOMDocument;
use DOMElement;
use DOMNodeList;
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
            $arguments = $this->loadPhpMdArguments();

            array_unshift($arguments, $this->getPhpMdPath());

            $process = new Process($arguments);
            $process->run();

            $this->write($process->getOutput());

            if ($process->isSuccessful()) {
                $this->success("PHPMD detected no errors, allowing commit to proceed.", false);
                return;
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

    /**
     * Return PHPMD arguments from configuration file
     *
     * @return array
     *
     * @throws RuntimeException
     */
    protected function loadPhpMdArguments()
    {
        return $this->parseArgumentsFromConfiguration();
    }

    /**
     * Parse arguments from PHPMD configuration file and convert them into command arguments
     *
     * @return array
     *
     * @throws RuntimeException
     */
    protected function parseArgumentsFromConfiguration()
    {
        $dom = new DOMDocument();
        $dom->loadXML(file_get_contents($this->getPhpMdConfigurationPath()));

        $configuration = $dom->getElementsByTagName('configuration');

        if ($configuration->length === 0) {
            throw new RuntimeException('No configuration section found');
        }

        if ($configuration->length > 1) {
            throw new RuntimeException('More than one configuration section found');
        }

        $configuration = $configuration->item(0);

        $arguments = [];

        array_push($arguments, $this->getSourceArgument($configuration));
        array_push($arguments, $this->getOutputModeArgument($configuration));
        array_push($arguments, $this->getPhpMdConfigurationPath());

        $excludes = $this->getExcludeArgument($configuration);

        if ($excludes !== '') {
            array_push($arguments, $excludes);
        }

        $minimumPriority = $this->getMinimumPriorityArgument($configuration);

        if ($minimumPriority !== '') {
            array_push($arguments, $minimumPriority);
        }

        $reportFile = $this->getReportFileArgument($configuration);

        if ($reportFile !== '') {
            array_push($arguments, $reportFile);
        }

        $suffixes = $this->getSuffixesArgument($configuration);

        if ($suffixes !== '') {
            array_push($arguments, $suffixes);
        }

        $strict = $this->getStrictArgument($configuration);

        if ($strict !== '') {
            array_push($arguments, $strict);
        }

        return $arguments;
    }

    /**
     * Generate the source argument
     *
     * @param DOMElement $configuration
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected function getSourceArgument(DOMElement $configuration)
    {
        $sources = $configuration->getElementsByTagName('source');

        if ($sources->length === 0) {
            throw new RuntimeException('No source defined');
        }

        return implode(',', $this->getPathsFromConfiguration($sources));
    }

    /**
     * Generate the exclude argument
     *
     * @param DOMElement $configuration
     *
     * @return string
     */
    protected function getExcludeArgument(DOMElement $configuration)
    {
        $excludes = $configuration->getElementsByTagName('exclude');

        if ($excludes->length === 0) {
            return '';
        }

        return '--exclude=' . implode(',', $this->getPathsFromConfiguration($excludes));
    }

    /**
     * Generate the output mode argument
     *
     * @param DOMElement $configuration
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected function getOutputModeArgument(DOMElement $configuration)
    {
        $outputMode = $configuration->getElementsByTagName('output');

        if ($outputMode->length === 0) {
            throw new RuntimeException('No output defined');
        }

        if ($outputMode->length > 1) {
            throw new RuntimeException('More than one output defined');
        }

        if (!$outputMode->item(0)->hasAttribute('mode')) {
            throw new RuntimeException('Output does not have a mode defined');
        }

        return $outputMode->item(0)->getAttribute('mode');
    }

    /**
     * Generate the minimumpriority argument
     *
     * @param DOMElement $configuration
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected function getMinimumPriorityArgument(DOMElement $configuration)
    {
        $minimumPriority = $configuration->getElementsByTagName('minimum-priority');

        if ($minimumPriority->length > 1) {
            throw new RuntimeException('More than one minimum priority defined');
        }

        if ($minimumPriority->length === 0) {
            return '';
        }

        if (!$minimumPriority->item(0)->hasAttribute('value')) {
            throw new RuntimeException('Minimum priority does not have a value defined');
        }

        return '--minimumpriority=' . $minimumPriority->item(0)->getAttribute('value');
    }

    /**
     * Generate the reportfile argument
     *
     * @param DOMElement $configuration
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected function getReportFileArgument(DOMElement $configuration)
    {
        $reportFile = $configuration->getElementsByTagName('report');

        if ($reportFile->length > 1) {
            throw new RuntimeException('More than one report file defined');
        }

        if ($reportFile->length === 0) {
            return '';
        }

        if (!$reportFile->item(0)->hasAttribute('file')) {
            throw new RuntimeException('Report file does not have a file defined');
        }

        return '--reportfile=' . $reportFile->item(0)->getAttribute('file');
    }

    /**
     * Generate the strict argument
     *
     * @param DOMElement $configuration
     *
     * @return string
     */
    protected function getStrictArgument(DOMElement $configuration)
    {
        $strict = $configuration->getElementsByTagName('strict');

        if ($strict->length > 0) {
            return '--strict';
        }

        return '';
    }

    /**
     * Generate the suffixes argument
     *
     * @param DOMElement $configuration
     *
     * @return string
     */
    protected function getSuffixesArgument(DOMElement $configuration)
    {
        $suffixes = $configuration->getElementsByTagName('suffixes');

        if ($suffixes->length === 0) {
            return '';
        }

        return '--suffixes=' . implode(',', $this->getSuffixesFromConfiguration($suffixes));
    }

    /**
     * Get Suffixes from a suffix listing node
     *
     * @param DOMNodeList $suffixesNodes
     *
     * @return array
     */
    protected function getSuffixesFromConfiguration(DOMNodeList $suffixesNodes)
    {
        $suffixes = [];
        $suffixesNodesLength = $suffixesNodes->length;

        for ($i = 0; $i < $suffixesNodesLength; $i++) {
            $suffixesNode = $suffixesNodes->item($i);
            $suffixNodes = $suffixesNode->getElementsByTagName('suffix');
            $suffixNodesLength = $suffixNodes->length;

            for ($j = 0; $j < $suffixNodesLength; $j++) {
                $suffixNode = $suffixNodes->item($j);

                array_push($suffixes, $suffixNode->textContent);
            }
        }

        return $suffixes;
    }

    /**
     * Get Paths from a path listing node
     *
     * @param DOMNodeList $sourceNodes
     *
     * @return array
     */
    protected function getPathsFromConfiguration(DOMNodeList $sourceNodes)
    {
        $sourcePaths = [];
        $sourceNodesLength = $sourceNodes->length;

        for ($i = 0; $i < $sourceNodesLength; $i++) {
            $sourceNode = $sourceNodes->item($i);
            $pathNodes = $sourceNode->getElementsByTagName('path');
            $pathNodesLength = $pathNodes->length;

            for ($j = 0; $j < $pathNodesLength; $j++) {
                $pathNode = $pathNodes->item($j);

                array_push($sourcePaths, $pathNode->textContent);
            }
        }

        return $sourcePaths;
    }
}
