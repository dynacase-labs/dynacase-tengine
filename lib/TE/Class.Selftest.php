<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
*/

class Selftest
{
    private $TE_HOME = '';
    private $selftests = array();
    public function __construct()
    {
        $this->loadSelftests();
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
        $cmd = sprintf("%s 2>&1", escapeshellarg($selftest));
        exec($cmd, $output, $ret);
        return ($ret == 0);
    }
}
