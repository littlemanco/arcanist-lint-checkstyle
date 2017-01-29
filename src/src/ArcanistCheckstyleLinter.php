<?php

/**
 * Implements the checkstyle lints
 *
 * @todo: Implement help URLs
 */
final class ArcanistCheckstyleLinter extends ArcanistLinter
{
    private $script = null;
    private $aOutput = array();

    /**
     * @return string
     */
    public function getInfoName()
    {
        return pht('Checkstyle');
    }

    /**
     * @return string
     */
    public function getInfoDescription()
    {
        return pht('Run an external script, then parse its output for checkstyle content');
    }

    /**
     * Run the script on each file to be linted.
     */
    public function willLintPaths(array $paths)
    {
        $root = $this->getProjectRoot();

        $futures = array();
        foreach ($paths as $path) {
            $future = new ExecFuture('%C %s', $this->script, $path);
            $future->setCWD($root);
            $futures[$path] = $future;
        }

        $futures = id(new FutureIterator($futures))
            ->limit(4);
        foreach ($futures as $path => $future) {
            list($stdout) = $future->resolvex();
            $this->aOutput[$path] = $stdout;
        }
    }

    /**
     * Parse the checkstyle output of the script
     */
    public function lintPath($sPath)
    {
        $sOutput = idx($this->aOutput, $sPath);

        if (!strlen($sOutput)) {
            // No sOutput, but it exited 0, so just move on.
            return;
        }

        $oCheckStyle    = new SimpleXMLElement($sOutput);

        // Descend to the node containing the lint errors
        $oErrors = $oCheckStyle->xpath('//file/error');

            foreach($oErrors as $oLine) {
                $aDict = $this->parseViolation($oLine);
                $aDict['path'] = $sPath;

                $oLint = ArcanistLintMessage::newFromDictionary($aDict);
                $this->addLintMessage($oLint);
            }
    }

    /**
     *
     * Checkstyle returns output of the form
     *
     * <checkstyle>
     *   <file name="${sPath}">
     *     <error line="${iLineNumber}" column="${iLineColumn}" severity="${sSeverity}" message="${sMessage}" source="${sSource}">
     *     ...
     *   </file>
     * </checkstyle>
     *
     * Of this, we need to extract
     *   - Line
     *   - Column
     *   - Severity
     *   - Message
     *   - Source (name)
     *
     * @param SimpleXMLElement $oXml The XML Entity containing the issue
     * @return array of the form
     * [
     *   'line' => {int},
     *   'column' => {int},
     *   'severity' => {string},
     *   'message' => {string}
     * ]
     */
    private function parseViolation(SimpleXMLElement $oXml)
    {
        return array(
            'code' => $this->getLinterName(),
            'name' => (string)$oXml['source'],
            'line' => (int)$oXml['line'],
            'char' => (int)$oXml['column'],
            'severity' => $this->getMatchSeverity((string)$oXml['severity']),
            'description' => (string)$oXml['message']
        );
    }


    /* -(  Linter Information  )------------------------------------------------- */

    /**
     * Return the short name of the linter.
     *
     * @return string Short linter identifier.
     *
     * @task linterinfo
     */
    public function getLinterName()
    {
        return 'Checkstyle';
    }

    public function getLinterConfigurationName()
    {
        return 'checkstyle';
    }

    public function getLinterConfigurationOptions()
    {
        // These fields are optional only to avoid breaking things.
        $options = array(
            'checkstyle.script' => array(
                'type' => 'string',
                'help' => pht('Script to execute.'),
            )
        );

        return $options + parent::getLinterConfigurationOptions();
    }

    public function setLinterConfigurationValue($key, $value)
    {
        switch ($key) {
            case 'checkstyle.script':
                $this->script = $value;
                return;
        }

        return parent::setLinterConfigurationValue($key, $value);
    }

    /* -(  Parsing Output  )----------------------------------------------------- */

    /**
     * Map the regex matching groups to a message severity. We look for either
     * a nonempty severity name group like 'error', or a group called 'severity'
     * with a valid name.
     *
     * @param dict Captured groups from regex.
     * @return const  @{class:ArcanistLintSeverity} constant.
     *
     * @task parse
     */
    private function getMatchSeverity($severity_name)
    {
        $map = array(
            'error' => ArcanistLintSeverity::SEVERITY_ERROR,
            'warning' => ArcanistLintSeverity::SEVERITY_WARNING,
            'info' => ArcanistLintSeverity::SEVERITY_ADVICE,
        );

        foreach ($map as $name => $severity) {
            if ($severity_name == $name) {
                return $severity;
            }
        }

        return ArcanistLintSeverity::SEVERITY_ERROR;
    }
}
