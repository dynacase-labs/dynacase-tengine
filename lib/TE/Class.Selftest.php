<?php
/*
 * @author Anakeen
*/

class Selftest
{
    private $TE_HOME = '';
    private $selftests = array();
    private $workDir = false;
    public function __construct($workDir)
    {
        if (!is_dir($workDir)) {
            throw new Exception(sprintf("Invalid work directory '%s'.", $workDir));
        }
        $testWorkDir = tempnam($workDir, 'te-task-test-');
        if ($testWorkDir === false) {
            throw new Exception(sprintf("Error creating test work directory in directory '%s'.", $workDir));
        }
        unlink($testWorkDir);
        if (mkdir($testWorkDir) === false) {
            throw new Exception(sprintf("Error creating test work directory '%s'.", $testWorkDir));
        }
        $this->workDir = $testWorkDir;
        $this->loadSelftests();
    }
    public function __destruct()
    {
        $this->deleteWorkDir();
    }
    private function deleteWorkDir()
    {
        if (!is_dir($this->workDir)) {
            return;
        }
        if (!preg_match('/^te-task-test-/', basename($this->workDir))) {
            return;
        }
        Task::rm_rf($this->workDir);
    }
    public function setTmpDir($tmpDir)
    {
        $TMPDIR = getenv('TMPDIR');
        if ($tmpDir === false) {
            putenv('TMPDIR');
        } else {
            putenv(sprintf('TMPDIR=%s', $tmpDir));
        }
        return $TMPDIR;
    }
    public function loadSelftests()
    {
        $this->TE_HOME = getenv('TE_HOME');
        $testDir = $this->TE_HOME . DIRECTORY_SEPARATOR . 'test-data';
        if (($fd = opendir($testDir)) === false) {
            throw new Exception(sprintf("Error opening test-data directory '%s'.", $testDir));
        }
        while (($name = readdir($fd)) !== false) {
            if (!preg_match('/^test_.*/', $name)) {
                continue;
            }
            $filepath = $testDir . DIRECTORY_SEPARATOR . $name;
            $description = $this->getDescription($filepath);
            $this->selftests[$name] = array(
                'description' => $description,
                'path' => $filepath
            );
        }
        closedir($fd);
    }
    private function getDescription($filepath)
    {
        $content = file_get_contents($filepath);
        if ($content !== false && preg_match('/^#\s+Description:\s+(?P<desc>.*?)\s*$/mi', $content, $m)) {
            return $m['desc'];
        }
        return basename($filepath);
    }
    public function getSelftests()
    {
        /* Hide pathnames */
        $selftests = $this->selftests;
        foreach ($selftests as & $selftest) {
            unset($selftest['path']);
        }
        return $selftests;
    }
    public function executeSelftest($selftestName, &$output)
    {
        if (!isset($this->selftests[$selftestName]['path'])) {
            throw new Exception(sprintf("Selftest '%s' does not exists.", $selftestName));
        }
        $selftest = $this->selftests[$selftestName]['path'];
        $TMPDIR = $this->setTmpDir($this->workDir);
        $cmd = sprintf("%s 2>&1", escapeshellarg($selftest));
        exec($cmd, $output, $ret);
        $this->setTmpDir($TMPDIR);
        return ($ret == 0);
    }
}
